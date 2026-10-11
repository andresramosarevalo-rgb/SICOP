<?php

use App\Enums\Rol;
use App\Models\Contrato;
use App\Models\ReciboNomina;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * El botón "Atrás" vuelve con el historial del navegador; sin historial, va a la página padre.
 * Estos tests comprueban que cada página de detalle recibe el dato de su página padre.
 * El criterio 3 (no aparece al imprimir) se verifica en el guion manual del plan de calidad.
 */

beforeEach(function () {
    $this->auxiliar = User::factory()->conRol(Rol::AuxiliarNomina)->create();
});

// Issue #19, criterios 1 y 2
test('el comprobante recibe el periodo al que se vuelve con Atrás', function () {
    $recibo = ReciboNomina::factory()->create();

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.recibos.show', $recibo))
        ->assertInertia(fn (Assert $page) => $page->where('periodo.id', $recibo->periodo_nomina_id));
});

// Issue #19, criterio 2
test('el formulario de contrato recibe el empleado al que se vuelve con Atrás', function () {
    $empleado = Contrato::factory()->create()->empleado;

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.empleados.contratos.create', $empleado))
        ->assertInertia(fn (Assert $page) => $page->where('empleado.id', $empleado->id));

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.empleados.edit', $empleado))
        ->assertInertia(fn (Assert $page) => $page->where('empleado.id', $empleado->id));
});
