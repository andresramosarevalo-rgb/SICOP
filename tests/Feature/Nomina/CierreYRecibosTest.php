<?php

use App\Actions\Nomina\LiquidarPeriodoNomina;
use App\Enums\EstadoPeriodo;
use App\Enums\Rol;
use App\Models\Contrato;
use App\Models\PeriodoNomina;
use App\Models\ReciboNomina;
use App\Models\User;
use Database\Seeders\NominaSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(NominaSeeder::class);
    $this->auxiliar = User::factory()->conRol(Rol::AuxiliarNomina)->create();
    $this->periodo = PeriodoNomina::factory()->create();
});

/**
 * Liquida el periodo de la prueba con un empleado de salario mínimo.
 */
function liquidarPeriodoDePrueba(PeriodoNomina $periodo, User $usuario): void
{
    Contrato::factory()->create(['valor_salario_base' => '1750905']);
    app(LiquidarPeriodoNomina::class)->ejecutar($periodo, $usuario);
}

// Issue #16, criterio 1
test('al cerrar un periodo liquidado queda cerrado y ya no se puede volver a liquidar', function () {
    liquidarPeriodoDePrueba($this->periodo, $this->auxiliar);

    $this->actingAs($this->auxiliar)
        ->post(route('nomina.periodos.cerrar', $this->periodo))
        ->assertRedirect(route('nomina.periodos.show', $this->periodo));

    expect($this->periodo->fresh())
        ->estado->toBe(EstadoPeriodo::Cerrado)
        ->fecha_cierre->not->toBeNull();

    $this->actingAs($this->auxiliar)
        ->post(route('nomina.periodos.liquidar', $this->periodo))
        ->assertSessionHasErrors(['periodo' => 'El periodo está cerrado y no se puede volver a liquidar.']);
});

// Issue #16, criterio 2
test('un periodo sin liquidar no se puede cerrar', function () {
    $this->actingAs($this->auxiliar)
        ->post(route('nomina.periodos.cerrar', $this->periodo))
        ->assertSessionHasErrors(['periodo' => 'Solo se puede cerrar un periodo liquidado.']);

    expect($this->periodo->fresh()->estado)->toBe(EstadoPeriodo::Borrador);
});

// Issue #16, criterio 3
test('el recibo muestra los devengos, las deducciones, los totales y el neto a pagar', function () {
    liquidarPeriodoDePrueba($this->periodo, $this->auxiliar);
    $recibo = ReciboNomina::sole();

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.recibos.show', $recibo))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Nomina/Recibos/Show')
            ->where('devengos.0.descripcion', 'Salario')
            ->where('devengos.1.descripcion', 'Auxilio de transporte')
            ->has('devengos', 2)
            ->has('deducciones', 2)
            ->where('recibo.valor_total_devengado', '2000000.00')
            ->where('recibo.valor_total_deducciones', '140072.00')
            ->where('recibo.valor_neto', '1859928.00')
            ->where('empleado.numero_documento', $recibo->empleado->numero_documento));
});

test('un cajero no puede ver un recibo de pago', function () {
    liquidarPeriodoDePrueba($this->periodo, $this->auxiliar);

    $this->actingAs(User::factory()->conRol(Rol::Cajero)->create())
        ->get(route('nomina.recibos.show', ReciboNomina::sole()))
        ->assertForbidden();
});
