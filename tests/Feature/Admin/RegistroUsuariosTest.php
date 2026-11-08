<?php

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

// Issue #146, criterio 1: registro y listado de usuarios.
test('el administrador registra un usuario con rol', function () {
    Notification::fake();

    $administrador = User::factory()
        ->conRol(Rol::Administrador)
        ->create(['name' => 'Zoe Administradora']);

    $respuesta = $this->actingAs($administrador)->post(
        route('admin.usuarios.store'),
        [
            'name' => 'Ana González',
            'email' => 'ana@example.com',
            'password' => 'ClaveSegura123!',
            'password_confirmation' => 'ClaveSegura123!',
            'rol' => Rol::AuxiliarNomina->value,
        ],
    );

    $respuesta->assertRedirect(route('admin.usuarios.index'));

    $usuario = User::where('email', 'ana@example.com')->firstOrFail();

    expect($usuario->rol)->toBe(Rol::AuxiliarNomina)
        ->and(Hash::check('ClaveSegura123!', $usuario->password))
        ->toBeTrue()
        ->and($usuario->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($usuario, VerifyEmail::class);

    $this->get(route('admin.usuarios.index'))
        ->assertInertia(fn (Assert $pagina) => $pagina
            ->component('Admin/Usuarios/Index')
            ->has('usuarios', 2)
            ->where('usuarios.0.email', 'ana@example.com')
            ->where('usuarios.0.rol', Rol::AuxiliarNomina->value)
            ->etc());
});

// Issue #146, criterio 2: correo duplicado.
test('no permite registrar un correo existente', function () {
    $administrador = User::factory()
        ->conRol(Rol::Administrador)
        ->create();

    User::factory()->create(['email' => 'repetido@example.com']);

    $this->actingAs($administrador)->post(
        route('admin.usuarios.store'),
        [
            'name' => 'Otro usuario',
            'email' => 'repetido@example.com',
            'password' => 'ClaveSegura123!',
            'password_confirmation' => 'ClaveSegura123!',
            'rol' => Rol::Contador->value,
        ],
    )->assertSessionHasErrors('email');

    $this->assertDatabaseCount('users', 2);
});

// Issue #146, criterio 3: acceso del auxiliar de nómina.
test('el auxiliar registrado solo accede al modulo permitido', function () {
    Notification::fake();

    $administrador = User::factory()
        ->conRol(Rol::Administrador)
        ->create();

    $this->actingAs($administrador)->post(
        route('admin.usuarios.store'),
        [
            'name' => 'Auxiliar de prueba',
            'email' => 'auxiliar@example.com',
            'password' => 'ClaveSegura123!',
            'password_confirmation' => 'ClaveSegura123!',
            'rol' => Rol::AuxiliarNomina->value,
        ],
    )->assertRedirect(route('admin.usuarios.index'));

    $auxiliar = User::where('email', 'auxiliar@example.com')
        ->firstOrFail();

    $this->actingAs($auxiliar)
        ->get(route('nomina.inicio.index'))
        ->assertRedirect(route('verification.notice'));

    // Simula que el propietario verificó su correo.
    $auxiliar->markEmailAsVerified();

    $this->actingAs($auxiliar)
        ->get(route('nomina.inicio.index'))
        ->assertOk();

    $this->get(route('pos.inicio.index'))->assertForbidden();
    $this->get(route('admin.auditoria.index'))->assertForbidden();

    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $pagina) => $pagina
            ->component('Dashboard')
            ->where('auth.permisos.gestionarNomina', true)
            ->where('auth.permisos.usarPos', false)
            ->where('auth.permisos.verAuditoria', false)
            ->where('auth.permisos.gestionarUsuarios', false)
            ->etc());
});

// Issue #146, criterio 4: protección de las rutas.
test('los usuarios no administradores no gestionan usuarios', function () {
    foreach ([
        Rol::AuxiliarNomina,
        Rol::Contador,
        Rol::Cajero,
    ] as $rol) {
        $usuario = User::factory()->conRol($rol)->create();

        $this->actingAs($usuario)
            ->get(route('admin.usuarios.index'))
            ->assertForbidden();

        $this->get(route('admin.usuarios.create'))
            ->assertForbidden();

        $this->post(route('admin.usuarios.store'), [
            'name' => 'Usuario prohibido',
            'email' => 'prohibido@example.com',
            'password' => 'ClaveSegura123!',
            'password_confirmation' => 'ClaveSegura123!',
            'rol' => Rol::Administrador->value,
        ])->assertForbidden();
    }

    $this->assertDatabaseMissing('users', [
        'email' => 'prohibido@example.com',
    ]);
});

// Issue #165, criterio 1: la sede es obligatoria para cajeros.
test('el cajero necesita una sede', function () {
    $administrador = User::factory()
        ->conRol(Rol::Administrador)
        ->create();

    $this->actingAs($administrador)->post(
        route('admin.usuarios.store'),
        [
            'name' => 'Cajero de prueba',
            'email' => 'cajero@example.com',
            'password' => 'ClaveSegura123!',
            'password_confirmation' => 'ClaveSegura123!',
            'rol' => Rol::Cajero->value,
        ],
    )->assertSessionHasErrors('sede_id');

    $this->assertDatabaseMissing('users', [
        'email' => 'cajero@example.com',
    ]);
});

// Issue #146, criterio 4: visitantes sin sesión.
test('los visitantes no pueden administrar usuarios', function () {
    $this->get(route('admin.usuarios.index'))
        ->assertRedirect(route('login'));

    $this->get(route('admin.usuarios.create'))
        ->assertRedirect(route('login'));
});
