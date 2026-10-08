<?php

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;

// Issue #4, criterios 1 y 2
test('solo administradores y auxiliares de nómina pueden gestionar la nómina', function (?Rol $rol, bool $puedeGestionar) {
    $user = User::factory()->create(['rol' => $rol]);

    expect(Gate::forUser($user)->allows('gestionar-nomina'))->toBe($puedeGestionar);
})->with([
    'administrador' => [Rol::Administrador, true],
    'auxiliar de nómina' => [Rol::AuxiliarNomina, true],
    'contador' => [Rol::Contador, false],
    'cajero' => [Rol::Cajero, false],
    'sin rol' => [null, false],
]);

// Issue #4, criterio 1
test('el auxiliar de nómina ve el módulo y el menú de nómina', function () {
    $user = User::factory()->conRol(Rol::AuxiliarNomina)->create();

    $this->actingAs($user)
        ->get(route('nomina.inicio.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Nomina/Inicio/Index')
            ->where('auth.permisos.gestionarNomina', true));
});

// Issue #4, criterio 2
test('el cajero recibe 403 en nómina y no ve el menú', function () {
    $user = User::factory()->conRol(Rol::Cajero)->create();

    $this->actingAs($user)
        ->get(route('nomina.inicio.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.permisos.gestionarNomina', false));
});

// Issue #4, criterio 3
test('un visitante sin sesión es redirigido al login', function () {
    $this->get(route('nomina.inicio.index'))
        ->assertRedirect(route('login'));
});
