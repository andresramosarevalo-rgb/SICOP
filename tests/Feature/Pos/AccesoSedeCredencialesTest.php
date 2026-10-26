<?php

use App\Enums\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gerente = User::factory()->conRol(Rol::Administrador)->create();
    $this->sede = Sede::factory()->create(['nombre' => 'Sede Villavicencio']);
});

function datosAcceso(array $sobrescribir = []): array
{
    return [
        'email' => 'villavicencio@example.com',
        'password' => 'Clave-segura-123',
        'password_confirmation' => 'Clave-segura-123',
        ...$sobrescribir,
    ];
}

// Issue #71, criterio 1
test('el gerente crea el acceso de la sede', function () {
    $this->actingAs($this->gerente)
        ->put(route('pos.sedes.acceso.update', $this->sede), datosAcceso())
        ->assertRedirect(route('pos.sedes.edit', $this->sede));

    $usuario = User::where('email', 'villavicencio@example.com')->firstOrFail();

    expect($usuario)
        ->name->toBe('Sede Villavicencio')
        ->rol->toBe(Rol::Cajero)
        ->sede_id->toBe($this->sede->id)
        ->email_verified_at->not->toBeNull()
        ->and(Hash::check('Clave-segura-123', $usuario->password))->toBeTrue();
});

// Issue #71, criterio 1
test('la sede puede iniciar sesión con el acceso que creó el gerente', function () {
    $this->actingAs($this->gerente)
        ->put(route('pos.sedes.acceso.update', $this->sede), datosAcceso());
    auth()->logout();

    $this->post(route('login.store'), ['email' => 'villavicencio@example.com', 'password' => 'Clave-segura-123'])
        ->assertRedirect();

    $this->assertAuthenticated();
});

// Issue #71, criterio 1
test('al crear el acceso la contraseña es obligatoria y debe confirmarse', function () {
    $this->actingAs($this->gerente)
        ->put(route('pos.sedes.acceso.update', $this->sede), datosAcceso(['password' => '', 'password_confirmation' => '']))
        ->assertSessionHasErrors('password');

    $this->actingAs($this->gerente)
        ->put(route('pos.sedes.acceso.update', $this->sede), datosAcceso(['password_confirmation' => 'otra-distinta']))
        ->assertSessionHasErrors(['password' => 'La confirmación no coincide con la contraseña.']);

    expect(User::where('sede_id', $this->sede->id)->exists())->toBeFalse();
});

// Issue #71, criterio 2
test('el gerente cambia el correo sin tener que escribir otra contraseña', function () {
    $usuario = User::factory()->paraSede($this->sede)->create();
    $claveAnterior = $usuario->password;

    $this->actingAs($this->gerente)
        ->put(route('pos.sedes.acceso.update', $this->sede), ['email' => 'nuevo@example.com', 'password' => '', 'password_confirmation' => ''])
        ->assertRedirect(route('pos.sedes.edit', $this->sede));

    expect($usuario->fresh())
        ->email->toBe('nuevo@example.com')
        ->password->toBe($claveAnterior)
        ->and(User::where('sede_id', $this->sede->id)->count())->toBe(1);
});

// Issue #71, criterio 2
test('el gerente puede cambiar la contraseña de la sede', function () {
    $usuario = User::factory()->paraSede($this->sede)->create();

    $this->actingAs($this->gerente)
        ->put(route('pos.sedes.acceso.update', $this->sede), datosAcceso(['email' => $usuario->email, 'password' => 'Otra-clave-456', 'password_confirmation' => 'Otra-clave-456']))
        ->assertRedirect();

    expect(Hash::check('Otra-clave-456', $usuario->fresh()->password))->toBeTrue();
});

// Issue #71, criterio 1
test('el correo no puede estar en uso por otro usuario', function () {
    User::factory()->create(['email' => 'villavicencio@example.com']);

    $this->actingAs($this->gerente)
        ->put(route('pos.sedes.acceso.update', $this->sede), datosAcceso())
        ->assertSessionHasErrors(['email' => 'Ese correo ya está en uso.']);
});

// Issue #71, criterio 1
test('cada sede tiene una sola cuenta', function () {
    User::factory()->paraSede($this->sede)->create();

    expect(fn () => User::factory()->paraSede($this->sede)->create())->toThrow(QueryException::class);
});

// Issue #71, criterio 1
test('el listado y la edición de la sede muestran su acceso', function () {
    $usuario = User::factory()->paraSede($this->sede)->create();
    Sede::factory()->create(['nombre' => 'Aaa sede sin acceso']);

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('sedes', 2)
            ->where('sedes.0.usuario', null)
            ->where('sedes.1.usuario.email', $usuario->email));

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.edit', $this->sede))
        ->assertInertia(fn (Assert $page) => $page->where('sede.usuario.email', $usuario->email));
});
