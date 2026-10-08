<?php

use App\Actions\Nomina\LiquidarPeriodoNomina;
use App\Enums\ConceptoSistema;
use App\Enums\EstadoPeriodo;
use App\Enums\PeriodicidadPago;
use App\Models\Contrato;
use App\Models\Empleado;
use App\Models\PeriodoNomina;
use App\Models\ReciboNomina;
use App\Models\User;
use Database\Seeders\NominaSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(NominaSeeder::class);
    $this->usuario = User::factory()->create();
    $this->liquidar = fn (PeriodoNomina $periodo) => app(LiquidarPeriodoNomina::class)->ejecutar($periodo, $this->usuario);
});

// Issue #13, criterio 1
test('liquidar un mes de salario mínimo genera el recibo con salario, auxilio, salud, pensión y neto', function () {
    $contrato = Contrato::factory()->create(['valor_salario_base' => '1750905']);
    $periodo = PeriodoNomina::factory()->create();

    ($this->liquidar)($periodo);

    $recibo = ReciboNomina::with('detalles.concepto')->sole();
    $valores = $recibo->detalles->mapWithKeys(fn ($detalle) => [$detalle->concepto->codigo => $detalle->valor_concepto]);

    expect($recibo->empleado_id)->toBe($contrato->empleado_id)
        ->and($valores->all())->toBe([
            ConceptoSistema::Salario->value => '1750905.00',
            ConceptoSistema::AuxilioTransporte->value => '249095.00',
            ConceptoSistema::Salud->value => '70036.00',
            ConceptoSistema::Pension->value => '70036.00',
        ])
        ->and($recibo->valor_neto)->toBe('1859928.00')
        ->and($periodo->fresh()->estado)->toBe(EstadoPeriodo::Liquidado)
        ->and($periodo->fresh()->liquidado_por)->toBe($this->usuario->id);
});

// Issue #13, criterio 2
test('un empleado con más de dos salarios mínimos no recibe auxilio de transporte', function () {
    Contrato::factory()->create(['valor_salario_base' => '4000000']);

    ($this->liquidar)(PeriodoNomina::factory()->create());

    expect(ReciboNomina::sole()->detalles()->count())->toBe(3)
        ->and(ReciboNomina::sole()->valor_total_devengado)->toBe('4000000.00');
});

// Issue #13, criterio 3
test('un periodo quincenal liquida el salario y el auxilio proporcionales a 15 días', function () {
    Contrato::factory()->create(['valor_salario_base' => '2000000', 'periodicidad_pago' => PeriodicidadPago::Quincenal]);
    $periodo = PeriodoNomina::factory()->create([
        'periodicidad_pago' => PeriodicidadPago::Quincenal, 'fecha_inicio' => '2026-02-01', 'fecha_fin' => '2026-02-15',
    ]);

    ($this->liquidar)($periodo);

    expect(ReciboNomina::sole())
        ->dias_liquidados->toBe(15)
        ->valor_total_devengado->toBe('1124548.00');
});

// Issue #13, criterio 4
test('volver a liquidar un periodo no cerrado reemplaza los recibos sin duplicarlos', function () {
    $contrato = Contrato::factory()->create(['valor_salario_base' => '1750905']);
    $periodo = PeriodoNomina::factory()->create();
    ($this->liquidar)($periodo);

    $contrato->update(['valor_salario_base' => '2000000']);
    ($this->liquidar)($periodo->fresh());

    expect(ReciboNomina::count())->toBe(1)
        ->and(ReciboNomina::sole()->valor_salario_base)->toBe('2000000.00');
});

// Issue #13, criterio 4
test('un periodo cerrado no se puede volver a liquidar', function () {
    Contrato::factory()->create();
    $periodo = PeriodoNomina::factory()->cerrado()->create();

    expect(fn () => ($this->liquidar)($periodo))->toThrow(ValidationException::class);
    expect(ReciboNomina::count())->toBe(0);
});

// Issue #13, criterio 5
test('solo se liquidan empleados activos con contrato vigente de la misma periodicidad', function () {
    $incluido = Contrato::factory()->create();
    Contrato::factory()->create(['periodicidad_pago' => PeriodicidadPago::Quincenal]);
    Contrato::factory()->create(['es_vigente' => false]);
    Contrato::factory()->for(Empleado::factory()->state(['es_activo' => false]))->create();
    Contrato::factory()->create(['fecha_inicio' => '2026-03-01']);

    ($this->liquidar)(PeriodoNomina::factory()->create());

    expect(ReciboNomina::pluck('empleado_id')->all())->toBe([$incluido->empleado_id]);
});

test('sin parámetros legales del año no se puede liquidar', function () {
    Contrato::factory()->create();
    $periodo = PeriodoNomina::factory()->create(['fecha_inicio' => '2027-01-01', 'fecha_fin' => '2027-01-31']);

    expect(fn () => ($this->liquidar)($periodo))
        ->toThrow(ValidationException::class, 'No hay parámetros legales registrados para 2027.');
});
