<?php

use App\Enums\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

// Issue #146, criterio 1: registro y listado de usuarios.
test('el super administrador registra un usuario con rol', function () {
    Notification::fake();

    $administrador = User::factory()
        ->conRol(Rol::SuperAdmin)
        ->create(['name' => 'Zoe Administradora']);

    $respuesta = $this->actingAs($administrador)->post(
        route('admin.usuarios.store'),
        [
            'name' => 'Ana González',
            'email' => 'ana@example.com',
            'password' => 'ClaveSegura123!',
            'password_confirmation' => 'ClaveSegura123!',
            'rol' => Rol::AuxiliarNomina->value,
            'sede_id' => Sede::factory()->create()->id,
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
        ->conRol(Rol::SuperAdmin)
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
            'sede_id' => Sede::factory()->create()->id,
        ],
    )->assertSessionHasErrors('email');

    $this->assertDatabaseCount('users', 2);
});

// Issue #146, criterio 3: acceso del auxiliar de nómina.
test('el auxiliar registrado solo accede al modulo permitido', function () {
    Notification::fake();

    $administrador = User::factory()
        ->conRol(Rol::SuperAdmin)
        ->create();

    $this->actingAs($administrador)->post(
        route('admin.usuarios.store'),
        [
            'name' => 'Auxiliar de prueba',
            'email' => 'auxiliar@example.com',
            'password' => 'ClaveSegura123!',
            'password_confirmation' => 'ClaveSegura123!',
            'rol' => Rol::AuxiliarNomina->value,
            'sede_id' => Sede::factory()->create()->id,
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
        $usuario = User::factory()->enSede(Sede::factory()->create(), $rol)->create();

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
            'rol' => Rol::SuperAdmin->value,
        ])->assertForbidden();
    }

    $this->assertDatabaseMissing('users', [
        'email' => 'prohibido@example.com',
    ]);
});

// Issue #165, criterio 1: la sede es obligatoria para cajeros.
test('el cajero necesita una sede', function () {
    $administrador = User::factory()
        ->conRol(Rol::SuperAdmin)
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

// Issue #173, criterio 1: todo usuario de sede se registra con sede.
test('el super administrador registra un usuario eligiendo la sede', function () {
    Notification::fake();

    $sede = Sede::factory()->create();
    $superAdmin = User::factory()->superAdmin()->create();

    foreach ([Rol::Administrador, Rol::Contador, Rol::AuxiliarNomina, Rol::Cajero] as $rol) {
        $datos = [
            'name' => "Usuario {$rol->value}",
            'email' => "{$rol->value}@example.com",
            'password' => 'ClaveSegura123!',
            'password_confirmation' => 'ClaveSegura123!',
            'rol' => $rol->value,
        ];

        $this->actingAs($superAdmin)
            ->post(route('admin.usuarios.store'), $datos)
            ->assertSessionHasErrors('sede_id');

        $this->post(route('admin.usuarios.store'), [...$datos, 'sede_id' => $sede->id])
            ->assertRedirect(route('admin.usuarios.index'));

        expect(User::where('email', $datos['email'])->firstOrFail()->sede_id)->toBe($sede->id);
    }
});

// Issue #173, criterio 2: el super administrador no lleva sede.
test('el super administrador creado no tiene sede', function () {
    Notification::fake();

    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)->post(route('admin.usuarios.store'), [
        'name' => 'Otro super',
        'email' => 'otro-super@example.com',
        'password' => 'ClaveSegura123!',
        'password_confirmation' => 'ClaveSegura123!',
        'rol' => Rol::SuperAdmin->value,
    ])->assertRedirect(route('admin.usuarios.index'));

    $usuario = User::where('email', 'otro-super@example.com')->firstOrFail();

    expect($usuario->rol)->toBe(Rol::SuperAdmin)->and($usuario->sede_id)->toBeNull();
});

// Issue #173, criterio 3: el administrador de sede solo registra en su sede.
test('el administrador de sede registra solo en su sede y no crea super administradores', function () {
    Notification::fake();

    $sedeA = Sede::factory()->create();
    $sedeB = Sede::factory()->create();
    $administrador = User::factory()->enSede($sedeA)->create();
    User::factory()->enSede($sedeB, Rol::Contador)->create(['name' => 'De otra sede']);

    $datos = [
        'name' => 'Auxiliar de A',
        'email' => 'auxiliar-a@example.com',
        'password' => 'ClaveSegura123!',
        'password_confirmation' => 'ClaveSegura123!',
        'rol' => Rol::AuxiliarNomina->value,
    ];

    $this->actingAs($administrador)
        ->post(route('admin.usuarios.store'), [...$datos, 'sede_id' => $sedeB->id])
        ->assertSessionHasErrors('sede_id');

    $this->post(route('admin.usuarios.store'), $datos)
        ->assertRedirect(route('admin.usuarios.index'));

    expect(User::where('email', 'auxiliar-a@example.com')->firstOrFail()->sede_id)->toBe($sedeA->id);

    $this->post(route('admin.usuarios.store'), [...$datos, 'email' => 'super@example.com', 'rol' => Rol::SuperAdmin->value])
        ->assertSessionHasErrors('rol');

    $this->get(route('admin.usuarios.index'))
        ->assertInertia(fn (Assert $pagina) => $pagina
            ->component('Admin/Usuarios/Index')
            ->has('usuarios', 2)
            ->where('usuarios.0.sede.id', $sedeA->id)
            ->where('usuarios.1.sede.id', $sedeA->id)
            ->etc());
});

// Issue #173, criterio 4: solo el super administrador gestiona sedes.
test('el administrador de sede no gestiona sedes', function () {
    $administrador = User::factory()->enSede(Sede::factory()->create())->create();

    $this->actingAs($administrador)->get(route('pos.sedes.index'))->assertForbidden();
    $this->get(route('pos.sedes.create'))->assertForbidden();

    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $pagina) => $pagina
            ->where('auth.permisos.gestionarSedes', false)
            ->where('auth.permisos.gestionarUsuarios', true)
            ->etc());
});

// Issue #173, criterio 5: los administradores existentes pasan a super administrador.
test('la migración convierte a los administradores sin sede en super administradores', function () {
    $administrador = User::factory()->create(['rol' => 'administrador', 'sede_id' => null]);
    $deSede = User::factory()->enSede(Sede::factory()->create())->create();

    (require database_path('migrations/2026_11_09_000001_convertir_administradores_en_super_admin.php'))->up();

    expect($administrador->refresh()->rol)->toBe(Rol::SuperAdmin)
        ->and($deSede->refresh()->rol)->toBe(Rol::Administrador);
});
