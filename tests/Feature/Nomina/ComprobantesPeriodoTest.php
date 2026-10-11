<?php

use App\Enums\Rol;
use App\Models\PeriodoNomina;
use App\Models\ReciboNomina;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// Issue #21, criterios 1 y 2
test('cada comprobante del periodo tiene su enlace y se abre', function () {
    $auxiliar = User::factory()->conRol(Rol::AuxiliarNomina)->create();
    $periodo = PeriodoNomina::factory()->create();
    $recibos = ReciboNomina::factory()->count(2)->for($periodo, 'periodo')->create();

    $this->actingAs($auxiliar)
        ->get(route('nomina.periodos.show', $periodo))
        ->assertInertia(fn (Assert $page) => $page
            ->has('recibos', 2)
            ->where('recibos.0.id', $recibos[0]->id)
            ->where('recibos.1.id', $recibos[1]->id));

    foreach ($recibos as $recibo) {
        $this->actingAs($auxiliar)
            ->get(route('nomina.recibos.show', $recibo))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Nomina/Recibos/Show')
                ->where('empleado.numero_documento', $recibo->empleado->numero_documento));
    }
});
