<?php

use App\Enums\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->sedeA = Sede::factory()->create();
    $this->sedeB = Sede::factory()->create();
    $this->superAdmin = User::factory()->superAdmin()->create();
    $this->administradorA = User::factory()->enSede($this->sedeA)->create();
    $this->auxiliarA = User::factory()->enSede($this->sedeA, Rol::AuxiliarNomina)->create(['name' => 'Auxiliar A']);
    $this->cajeroB = User::factory()->enSede($this->sedeB, Rol::Cajero)->create();
});

// Issue #178, criterio 1: editar el nombre.
test('el super administrador y el administrador de sede editan el nombre de los usuarios que gestionan', function () {
    $this->actingAs($this->administradorA)
        ->get(route('admin.usuarios.edit', $this->auxiliarA))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Usuarios/Edit')
            ->where('usuario.name', 'Auxiliar A'));

    $this->put(route('admin.usuarios.update', $this->auxiliarA), ['name' => 'Auxiliar Renombrada'])
        ->assertRedirect(route('admin.usuarios.index'));

    $this->actingAs($this->superAdmin)
        ->put(route('admin.usuarios.update', $this->cajeroB), ['name' => 'Cajero B Nuevo'])
        ->assertRedirect(route('admin.usuarios.index'));

    expect($this->auxiliarA->fresh()->name)->toBe('Auxiliar Renombrada')
        ->and($this->cajeroB->fresh()->name)->toBe('Cajero B Nuevo');

    $this->put(route('admin.usuarios.update', $this->cajeroB), ['name' => ''])->assertSessionHasErrors('name');
});

// Issue #178, criterio 2: el usuario deshabilitado no entra y se cierra su sesión.
test('un usuario deshabilitado no inicia sesión y pierde sus sesiones abiertas', function () {
    DB::table('sessions')->insert([
        'id' => 'sesion-abierta',
        'user_id' => $this->auxiliarA->id,
        'payload' => '',
        'last_activity' => time(),
    ]);

    $this->actingAs($this->administradorA)
        ->patch(route('admin.usuarios.estado', $this->auxiliarA), ['es_activo' => false])
        ->assertRedirect(route('admin.usuarios.index'));

    expect($this->auxiliarA->fresh()->es_activo)->toBeFalse();
    $this->assertDatabaseMissing('sessions', ['id' => 'sesion-abierta']);

    auth()->logout();

    $this->post(route('login.store'), ['email' => $this->auxiliarA->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Su usuario está deshabilitado. Comuníquese con el administrador.']);

    $this->assertGuest();
});

// Issue #178, criterio 3: al habilitarlo vuelve a entrar.
test('un usuario habilitado de nuevo vuelve a iniciar sesión', function () {
    $this->auxiliarA->forceFill(['es_activo' => false])->save();

    $this->actingAs($this->administradorA)
        ->patch(route('admin.usuarios.estado', $this->auxiliarA), ['es_activo' => true])
        ->assertRedirect(route('admin.usuarios.index'));

    auth()->logout();

    $this->post(route('login.store'), ['email' => $this->auxiliarA->email, 'password' => 'password'])
        ->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($this->auxiliarA);
});

// Issue #178, criterio 4: alcance del administrador de sede.
test('el administrador de sede solo gestiona usuarios de su sede y no super administradores', function () {
    $this->actingAs($this->administradorA)
        ->get(route('admin.usuarios.edit', $this->cajeroB))->assertForbidden();
    $this->put(route('admin.usuarios.update', $this->cajeroB), ['name' => 'Otro'])->assertForbidden();
    $this->patch(route('admin.usuarios.estado', $this->cajeroB), ['es_activo' => false])->assertForbidden();
    $this->patch(route('admin.usuarios.estado', $this->superAdmin), ['es_activo' => false])->assertForbidden();

    expect($this->cajeroB->fresh()->es_activo)->toBeTrue()
        ->and($this->superAdmin->fresh()->es_activo)->toBeTrue();

    $this->get(route('admin.usuarios.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('usuarios', 2)
            ->where('usuarios.0.puede_gestionar', true));
});

test('quien no gestiona usuarios no puede editarlos ni deshabilitarlos', function () {
    $this->actingAs($this->auxiliarA)
        ->patch(route('admin.usuarios.estado', $this->administradorA), ['es_activo' => false])
        ->assertForbidden();
});

// Issue #178, criterio 5: nadie se deshabilita a sí mismo.
test('nadie se deshabilita ni se edita a sí mismo', function () {
    foreach ([$this->superAdmin, $this->administradorA] as $usuario) {
        $this->actingAs($usuario)
            ->patch(route('admin.usuarios.estado', $usuario), ['es_activo' => false])
            ->assertForbidden();

        expect($usuario->fresh()->es_activo)->toBeTrue();
    }

    $this->actingAs($this->superAdmin)
        ->get(route('admin.usuarios.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('usuarios', 4)
            ->where('usuarios.0.puede_gestionar', true)
            ->etc());
});
