<?php

use App\Enums\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->sedeA = Sede::factory()->create();
    $this->sedeB = Sede::factory()->create();
    $this->superAdmin = User::factory()->superAdmin()->create();
    $this->administradorA = User::factory()->enSede($this->sedeA)->create();
    $this->auxiliarA = User::factory()->enSede($this->sedeA, Rol::AuxiliarNomina)->create();
    $this->cajeroB = User::factory()->enSede($this->sedeB, Rol::Cajero)->create();
});

/**
 * Datos del formulario de edición de un usuario.
 *
 * @param  array<string, mixed>  $cambios
 * @return array<string, mixed>
 */
function datosEdicionUsuario(User $usuario, array $cambios = []): array
{
    return ['name' => $usuario->name, 'email' => $usuario->email, ...$cambios];
}

// Issue #185, criterio 1: cambiar el correo.
test('al cambiar el correo debe verificarse de nuevo y se envía la verificación', function () {
    Notification::fake();

    $this->actingAs($this->administradorA)
        ->put(route('admin.usuarios.update', $this->auxiliarA), datosEdicionUsuario($this->auxiliarA, ['email' => 'nuevo@example.com']))
        ->assertRedirect(route('admin.usuarios.index'));

    $usuario = $this->auxiliarA->fresh();

    expect($usuario->email)->toBe('nuevo@example.com')
        ->and($usuario->email_verified_at)->toBeNull();

    Notification::assertSentTo($usuario, VerifyEmail::class);
});

test('guardar sin cambiar el correo no lo vuelve a verificar', function () {
    Notification::fake();

    $this->actingAs($this->administradorA)
        ->put(route('admin.usuarios.update', $this->auxiliarA), datosEdicionUsuario($this->auxiliarA, ['name' => 'Otro nombre']))
        ->assertRedirect(route('admin.usuarios.index'));

    expect($this->auxiliarA->fresh()->email_verified_at)->not->toBeNull();

    Notification::assertNothingSent();
});

test('no se guarda un correo que ya existe', function () {
    $this->actingAs($this->superAdmin)
        ->put(route('admin.usuarios.update', $this->auxiliarA), datosEdicionUsuario($this->auxiliarA, ['email' => $this->cajeroB->email]))
        ->assertSessionHasErrors(['email' => 'Ya existe un usuario con este correo.']);

    expect($this->auxiliarA->fresh()->email)->not->toBe($this->cajeroB->email);
});

// Issue #185, criterio 2: restablecer la contraseña.
test('el super administrador y el administrador de sede restablecen la contraseña y se cierran las sesiones', function () {
    DB::table('sessions')->insert(['id' => 'sesion-abierta', 'user_id' => $this->auxiliarA->id, 'payload' => '', 'last_activity' => time()]);

    $this->actingAs($this->administradorA)
        ->put(route('admin.usuarios.update', $this->auxiliarA), datosEdicionUsuario($this->auxiliarA, [
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'NuevaClave123!',
        ]))
        ->assertRedirect(route('admin.usuarios.index'));

    $this->assertDatabaseMissing('sessions', ['id' => 'sesion-abierta']);

    auth()->logout();

    $this->post(route('login.store'), ['email' => $this->auxiliarA->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->post(route('login.store'), ['email' => $this->auxiliarA->email, 'password' => 'NuevaClave123!']);
    $this->assertAuthenticatedAs($this->auxiliarA);

    $this->actingAs($this->superAdmin)
        ->put(route('admin.usuarios.update', $this->cajeroB), datosEdicionUsuario($this->cajeroB, [
            'password' => 'OtraClave123!',
            'password_confirmation' => 'OtraClave123!',
        ]))
        ->assertRedirect(route('admin.usuarios.index'));
});

test('la nueva contraseña debe confirmarse', function () {
    $this->actingAs($this->superAdmin)
        ->put(route('admin.usuarios.update', $this->auxiliarA), datosEdicionUsuario($this->auxiliarA, [
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'Distinta123!',
        ]))
        ->assertSessionHasErrors('password');
});

// Issue #185, criterio 3: contraseña vacía conserva la actual.
test('dejar la contraseña vacía conserva la actual', function () {
    $anterior = $this->auxiliarA->password;

    $this->actingAs($this->administradorA)
        ->put(route('admin.usuarios.update', $this->auxiliarA), datosEdicionUsuario($this->auxiliarA, ['password' => '', 'password_confirmation' => '']))
        ->assertRedirect(route('admin.usuarios.index'));

    expect($this->auxiliarA->fresh()->password)->toBe($anterior);
});

// Issue #185, criterio 4: alcance.
test('el administrador de sede no cambia correo ni contraseña de otra sede, de un super administrador ni suyos', function () {
    foreach ([$this->cajeroB, $this->superAdmin, $this->administradorA] as $objetivo) {
        $this->actingAs($this->administradorA)
            ->put(route('admin.usuarios.update', $objetivo), datosEdicionUsuario($objetivo, [
                'email' => 'tomado@example.com',
                'password' => 'NuevaClave123!',
                'password_confirmation' => 'NuevaClave123!',
            ]))
            ->assertForbidden();

        expect($objetivo->fresh()->email)->not->toBe('tomado@example.com');
    }
});
