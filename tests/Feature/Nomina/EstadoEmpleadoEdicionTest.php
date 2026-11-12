<?php

use App\Enums\Rol;
use App\Models\Empleado;
use App\Models\Sede;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->sede = Sede::factory()->create();
    $this->auxiliar = User::factory()->enSede($this->sede, Rol::AuxiliarNomina)->create();
    $this->empleado = Empleado::factory()->create(['sede_id' => $this->sede->id]);
});

// Issue #177, criterio 1: la edición muestra el estado y permite cambiarlo.
test('la edición del empleado incluye su estado y se puede desactivar y activar', function () {
    $this->actingAs($this->auxiliar)
        ->get(route('nomina.empleados.edit', $this->empleado))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Nomina/Empleados/Edit')
            ->where('empleado.es_activo', true));

    $this->patch(route('nomina.empleados.estado', $this->empleado), ['es_activo' => false])->assertRedirect();
    expect($this->empleado->fresh()->es_activo)->toBeFalse();

    $this->patch(route('nomina.empleados.estado', $this->empleado), ['es_activo' => true])->assertRedirect();
    expect($this->empleado->fresh()->es_activo)->toBeTrue();
});

// Issue #177, criterio 2: el empleado desactivado conserva su expediente.
test('el empleado desactivado conserva su expediente', function () {
    $this->actingAs($this->auxiliar)
        ->patch(route('nomina.empleados.estado', $this->empleado), ['es_activo' => false]);

    $this->get(route('nomina.empleados.show', $this->empleado))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('empleado.es_activo', false));
});

// Issue #177, criterio 3: no cambia el estado de empleados de otra sede.
test('no se cambia el estado de un empleado de otra sede', function () {
    $deOtraSede = Empleado::factory()->create(['sede_id' => Sede::factory()->create()->id]);

    $this->actingAs($this->auxiliar)
        ->patch(route('nomina.empleados.estado', $deOtraSede), ['es_activo' => false])
        ->assertNotFound();

    expect($deOtraSede->fresh()->es_activo)->toBeTrue();
});
