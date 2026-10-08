<?php

use App\Enums\Rol;
use App\Models\Area;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->auxiliar = User::factory()->conRol(Rol::AuxiliarNomina)->create();
});

// Issue #5, criterio 1
test('al guardar un área nueva queda registrada como activa y aparece en el listado', function () {
    $this->actingAs($this->auxiliar)
        ->post(route('nomina.areas.store'), ['nombre' => 'Atención al cliente'])
        ->assertRedirect(route('nomina.areas.index'));

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.areas.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Nomina/Areas/Index')
            ->has('areas', 1)
            ->where('areas.0.nombre', 'Atención al cliente')
            ->where('areas.0.es_activa', true));
});

// Issue #5, criterio 2
test('no se puede crear un área con un nombre que ya existe', function () {
    Area::factory()->create(['nombre' => 'Instrucción']);

    $this->actingAs($this->auxiliar)
        ->post(route('nomina.areas.store'), ['nombre' => 'Instrucción'])
        ->assertSessionHasErrors(['nombre' => 'Ya existe un área con ese nombre.']);

    expect(Area::count())->toBe(1);
});

test('un cajero no puede crear áreas', function () {
    $cajero = User::factory()->conRol(Rol::Cajero)->create();

    $this->actingAs($cajero)
        ->post(route('nomina.areas.store'), ['nombre' => 'Caja'])
        ->assertForbidden();

    expect(Area::count())->toBe(0);
});
