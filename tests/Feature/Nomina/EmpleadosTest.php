<?php

use App\Enums\Rol;
use App\Models\Area;
use App\Models\Empleado;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->auxiliar = User::factory()->conRol(Rol::AuxiliarNomina)->create();
});

// Issue #6, criterio 3
test('la búsqueda solo muestra los empleados que coinciden', function (string $buscar) {
    Empleado::factory()->create(['numero_documento' => '1012345678', 'nombres' => 'Laura', 'apellidos' => 'Gómez']);
    Empleado::factory()->create(['numero_documento' => '79888777', 'nombres' => 'Pedro', 'apellidos' => 'Rincón']);

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.empleados.index', ['buscar' => $buscar]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Nomina/Empleados/Index')
            ->has('empleados', 1)
            ->where('empleados.0.numero_documento', '1012345678'));
})->with([
    'por documento' => ['10123'],
    'por nombre' => ['laura'],
    'por apellido sin distinguir mayúsculas' => ['GóMEZ'],
]);

// Issue #6, criterio 3
test('sin búsqueda se listan todos los empleados', function () {
    Empleado::factory()->count(3)->create();

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.empleados.index'))
        ->assertInertia(fn (Assert $page) => $page->has('empleados', 3));
});

// Issue #5, criterio 3
test('un empleado conserva su área aunque el área se desactive', function () {
    $area = Area::factory()->create(['nombre' => 'Caja']);
    Empleado::factory()->for($area)->create();

    $area->update(['es_activa' => false]);

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.empleados.index'))
        ->assertInertia(fn (Assert $page) => $page->where('empleados.0.area.nombre', 'Caja'));
});

test('un cajero no puede ver los empleados', function () {
    $cajero = User::factory()->conRol(Rol::Cajero)->create();

    $this->actingAs($cajero)
        ->get(route('nomina.empleados.index'))
        ->assertForbidden();
});
