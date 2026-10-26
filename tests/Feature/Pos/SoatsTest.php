<?php

use App\Enums\Rol;
use App\Models\Sede;
use App\Models\Soat;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gerente = User::factory()->conRol(Rol::Administrador)->create();
    $this->sede = Sede::factory()->create();
});

function datosSoat(array $sobrescribir = []): array
{
    return [
        'fecha' => today()->toDateString(),
        'tipo_documento' => 'CC',
        'numero_documento' => '1006123456',
        'nombre_cliente' => 'Carlos Pérez',
        'valor_soat' => '580000',
        ...$sobrescribir,
    ];
}

// Issue #70, criterio 1
test('el listado muestra los SOAT de la sede, los más recientes primero', function () {
    Soat::factory()->for($this->sede)->create(['fecha' => '2026-10-14', 'nombre_cliente' => 'Antiguo', 'valor_soat' => 580000]);
    Soat::factory()->for($this->sede)->create(['fecha' => '2026-10-15', 'nombre_cliente' => 'Reciente']);

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.soats.index', $this->sede))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Soats/Index')
            ->where('sede.id', $this->sede->id)
            ->has('soats', 2)
            ->where('soats.0.nombre_cliente', 'Reciente')
            ->where('soats.1.nombre_cliente', 'Antiguo')
            ->where('soats.1.valor_soat', '580000.00'));
});

// Issue #70, criterio 1
test('el listado de una sede no muestra los SOAT de otra', function () {
    Soat::factory()->create();
    Soat::factory()->for($this->sede)->create();

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.soats.index', $this->sede))
        ->assertInertia(fn (Assert $page) => $page->has('soats', 1));
});

// Issue #67, criterio 2
test('el cajero recibe 403 en los SOAT', function () {
    $cajero = User::factory()->conRol(Rol::Cajero)->create();

    $this->actingAs($cajero)->get(route('pos.sedes.soats.index', $this->sede))->assertForbidden();
});
