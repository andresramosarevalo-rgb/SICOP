<?php

use App\Enums\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

// Issue #165, criterio 1: la sede es obligatoria y debe estar activa.
test('un cajero exige sede activa', function () {
    $admin = User::factory()->conRol(Rol::Administrador)->create();
    $sede = Sede::factory()->create(['es_activa' => false]);
    $datos = [
        'name' => 'Cajero',
        'email' => 'cajero@example.com',
        'password' => 'ClaveSegura123!',
        'password_confirmation' => 'ClaveSegura123!',
        'rol' => Rol::Cajero->value,
    ];
    $this->actingAs($admin)->post(route('admin.usuarios.store'), $datos)->assertSessionHasErrors('sede_id');
    $this->post(route('admin.usuarios.store'), [...$datos, 'sede_id' => $sede->id])->assertSessionHasErrors('sede_id');
});

// Issue #165, criterio 2: el personal administrativo no tiene sede.
test('la sede no se asocia a roles administrativos', function () {
    $admin = User::factory()->conRol(Rol::Administrador)->create();
    $sede = Sede::factory()->create();
    $this->actingAs($admin)->post(route('admin.usuarios.store'), [
        'name' => 'Contador',
        'email' => 'contador@example.com',
        'password' => 'ClaveSegura123!',
        'password_confirmation' => 'ClaveSegura123!',
        'rol' => Rol::Contador->value,
        'sede_id' => $sede->id,
    ])->assertSessionHasErrors('sede_id');
});

// Issue #165, criterio 3: una sede puede tener múltiples cajeros que inician sesión.
test('dos cajeros comparten sede e inician sesion con cuentas propias', function () {
    $admin = User::factory()->conRol(Rol::Administrador)->create();
    $sede = Sede::factory()->create();
    foreach (['cajero1@example.com', 'cajero2@example.com'] as $correo) {
        $this->actingAs($admin)->post(route('admin.usuarios.store'), [
            'name' => 'Cajero',
            'email' => $correo,
            'password' => 'ClaveSegura123!',
            'password_confirmation' => 'ClaveSegura123!',
            'rol' => Rol::Cajero->value,
            'sede_id' => $sede->id,
        ])->assertRedirect(route('admin.usuarios.index'));
    }

    expect($sede->usuarios()->count())->toBe(2);

    foreach (['cajero1@example.com', 'cajero2@example.com'] as $correo) {
        auth()->logout();
        $cajero = User::where('email', $correo)->firstOrFail();
        $cajero->markEmailAsVerified();
        $this->post(route('login.store'), ['email' => $correo, 'password' => 'ClaveSegura123!'])
            ->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($cajero);
    }
});

// Issue #165, criterio 4: los accesos se administran en Usuarios.
test('sedes no tienen ruta de acceso unico', function () {
    expect(Route::has('pos.sedes.acceso.update'))->toBeFalse();
    $admin = User::factory()->conRol(Rol::Administrador)->create();
    $sede = Sede::factory()->create();
    User::factory()->paraSede($sede)->count(2)->create();
    $this->actingAs($admin)->get(route('pos.sedes.index'))
        ->assertInertia(fn (Assert $pagina) => $pagina->where('sedes.0.usuarios_count', 2)->missing('sedes.0.usuario'));
});

// Issue #165, criterio 5: no existe acceso cruzado entre sedes.
test('el cajero no accede a otra sede ni a gestion', function () {
    $sede = Sede::factory()->create();
    $otra = Sede::factory()->create();
    $cajero = User::factory()->paraSede($sede)->create();

    $this->actingAs($cajero)->get(route('pos.sedes.cursos.index', $sede))->assertOk();
    $this->get(route('pos.sedes.soats.index', $sede))->assertOk();
    $this->get(route('pos.sedes.cierre.show', $sede))->assertOk();
    $this->get(route('pos.sedes.cursos.index', $otra))->assertForbidden();
    $this->get(route('pos.sedes.soats.index', $otra))->assertForbidden();
    $this->get(route('pos.sedes.cierre.show', $otra))->assertForbidden();

    foreach (['pos.sedes.index', 'pos.tarifas.index', 'pos.cursos.index', 'pos.soats.index',
        'pos.cierres.index', 'nomina.inicio.index', 'admin.auditoria.index', 'admin.usuarios.index'] as $ruta) {
        $this->get(route($ruta))->assertForbidden();
    }
});

// Issue #165, criterio 5: inactivar sede bloquea la autenticación.
test('cajero de sede inactiva no inicia sesion', function () {
    $sede = Sede::factory()->create(['es_activa' => false]);
    $cajero = User::factory()->paraSede($sede)->create();
    $this->post(route('login.store'), ['email' => $cajero->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

// Issue #165, criterio 6: se conserva correo único por cajero.
test('cajeros no pueden compartir correo', function () {
    $admin = User::factory()->conRol(Rol::Administrador)->create();
    $sede = Sede::factory()->create();
    User::factory()->paraSede($sede)->create(['email' => 'repetido@example.com']);
    $this->actingAs($admin)->post(route('admin.usuarios.store'), [
        'name' => 'Otro cajero',
        'email' => 'repetido@example.com',
        'password' => 'ClaveSegura123!',
        'password_confirmation' => 'ClaveSegura123!',
        'rol' => Rol::Cajero->value,
        'sede_id' => $sede->id,
    ])->assertSessionHasErrors('email');
});
