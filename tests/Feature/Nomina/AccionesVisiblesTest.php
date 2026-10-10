<?php

use App\Enums\EstadoPeriodo;
use App\Enums\Rol;
use App\Models\PeriodoNomina;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->auxiliar = User::factory()->conRol(Rol::AuxiliarNomina)->create();
});

// Issue #22, criterio 3
test('cada periodo del listado tiene su botón Abrir hacia el periodo', function () {
    $periodo = PeriodoNomina::factory()->create();

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.periodos.index'))
        ->assertInertia(fn (Assert $page) => $page->where('periodos.0.id', $periodo->id));

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.periodos.show', $periodo))
        ->assertOk();
});

// Issue #22, criterio 4
test('el periodo recibe el estado y la periodicidad que usa la guía de cada paso', function (EstadoPeriodo $estado) {
    $periodo = PeriodoNomina::factory()->create(['estado' => $estado]);

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.periodos.show', $periodo))
        ->assertInertia(fn (Assert $page) => $page
            ->where('periodo.estado', $estado->value)
            ->where('periodo.periodicidad_pago', 'mensual'));
})->with([EstadoPeriodo::Borrador, EstadoPeriodo::Liquidado, EstadoPeriodo::Cerrado]);

// Issue #22, criterio 5
test('el inicio del módulo muestra la página con los pasos para liquidar', function () {
    $this->actingAs($this->auxiliar)
        ->get(route('nomina.inicio.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Nomina/Inicio/Index'));
});
