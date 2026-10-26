<?php

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gerente = User::factory()->conRol(Rol::Administrador)->create();
});

function datosSede(array $sobrescribir = []): array
{
    return [
        'nombre' => 'Sede Villavicencio',
        'ciudad' => 'Villavicencio',
        'direccion' => 'Calle 15 # 30-20',
        ...$sobrescribir,
    ];
}

// Issue #67, criterios 1 y 2
test('solo el administrador puede gestionar el punto de venta', function (?Rol $rol, bool $puedeGestionar) {
    $user = User::factory()->create(['rol' => $rol]);

    expect(Gate::forUser($user)->allows('gestionar-pos'))->toBe($puedeGestionar);
})->with([
    'administrador' => [Rol::Administrador, true],
    'auxiliar de nómina' => [Rol::AuxiliarNomina, false],
    'contador' => [Rol::Contador, false],
    'cajero' => [Rol::Cajero, false],
    'sin rol' => [null, false],
]);

// Issue #67, criterio 1
test('el administrador ve el módulo y el menú de punto de venta', function () {
    $this->actingAs($this->gerente)
        ->get(route('pos.inicio.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Inicio/Index')
            ->where('auth.permisos.gestionarPos', true));
});
