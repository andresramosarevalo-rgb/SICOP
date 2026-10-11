<?php

use App\Enums\Rol;
use App\Models\Contrato;
use App\Models\PeriodoNomina;
use App\Models\User;
use Database\Seeders\NominaSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->auxiliar = User::factory()->conRol(Rol::AuxiliarNomina)->create();
});

// Issue #13, criterio 1
test('al liquidar un periodo se muestran sus recibos con el neto a pagar', function () {
    $this->seed(NominaSeeder::class);
    Contrato::factory()->create(['valor_salario_base' => '1750905']);
    $periodo = PeriodoNomina::factory()->create();

    $this->actingAs($this->auxiliar)
        ->post(route('nomina.periodos.liquidar', $periodo))
        ->assertRedirect(route('nomina.periodos.show', $periodo));

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.periodos.show', $periodo))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Nomina/Periodos/Show')
            ->where('periodo.estado', 'liquidado')
            ->has('recibos', 1)
            ->where('recibos.0.valor_neto', '1859928.00'));
});

// Issue #13, criterio 4
test('liquidar un periodo cerrado devuelve el error al usuario', function () {
    $this->seed(NominaSeeder::class);
    $periodo = PeriodoNomina::factory()->cerrado()->create();

    $this->actingAs($this->auxiliar)
        ->from(route('nomina.periodos.show', $periodo))
        ->post(route('nomina.periodos.liquidar', $periodo))
        ->assertSessionHasErrors(['periodo' => 'El periodo está cerrado y no se puede volver a liquidar.']);
});

test('el listado muestra los periodos con su cantidad de recibos', function () {
    PeriodoNomina::factory()->create();

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.periodos.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Nomina/Periodos/Index')
            ->where('periodos.0.recibos_count', 0));
});
