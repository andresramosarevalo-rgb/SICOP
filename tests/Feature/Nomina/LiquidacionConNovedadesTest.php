<?php

use App\Actions\Nomina\LiquidarPeriodoNomina;
use App\Enums\ConceptoSistema;
use App\Enums\TipoNovedad;
use App\Models\AsignacionConcepto;
use App\Models\ConceptoNomina;
use App\Models\Contrato;
use App\Models\Novedad;
use App\Models\PeriodoNomina;
use App\Models\ReciboNomina;
use App\Models\User;
use Database\Seeders\NominaSeeder;

beforeEach(function () {
    $this->seed(NominaSeeder::class);
    $this->contrato = Contrato::factory()->create(['valor_salario_base' => '2100000']);
    $this->periodo = PeriodoNomina::factory()->create();
    $this->liquidar = fn () => app(LiquidarPeriodoNomina::class)->ejecutar($this->periodo->fresh(), User::factory()->create());
});

/**
 * Valores del recibo del periodo, por código de concepto.
 *
 * @return array<string, string>
 */
function valoresDelRecibo(): array
{
    return ReciboNomina::sole()->detalles()->with('concepto')->get()
        ->mapWithKeys(fn ($detalle) => [$detalle->concepto->codigo => $detalle->valor_concepto])->all();
}

// Issue #14, criterios 1 y 2
test('la liquidación aplica las horas extra y las faltas registradas del empleado', function () {
    Novedad::factory()->for($this->contrato->empleado)->create(['tipo' => TipoNovedad::HoraExtraDiurna, 'cantidad' => 2]);
    Novedad::factory()->for($this->contrato->empleado)->create(['tipo' => TipoNovedad::Falta, 'cantidad' => null, 'fecha_inicio' => '2026-02-16']);

    ($this->liquidar)();

    expect(valoresDelRecibo())
        ->toMatchArray([
            ConceptoSistema::HoraExtraDiurna->value => '25000.00',
            ConceptoSistema::Salario->value => '2030000.00',
        ])
        ->and(ReciboNomina::sole()->dias_liquidados)->toBe(29);
});

// Issue #14, criterio 4
test('la liquidación incluye los conceptos recurrentes vigentes del empleado', function () {
    $bono = ConceptoNomina::factory()->create(['codigo' => 'BONO_PERMANENCIA', 'valor_base' => '100000']);
    AsignacionConcepto::factory()->for($this->contrato->empleado)->create(['concepto_nomina_id' => $bono->id, 'valor_asignado' => null]);

    ($this->liquidar)();

    expect(valoresDelRecibo())->toHaveKey('BONO_PERMANENCIA', '100000.00');
});

// Issue #14, criterio 5
test('las novedades liquidadas quedan asociadas al periodo y las de otros periodos no', function () {
    $delPeriodo = Novedad::factory()->for($this->contrato->empleado)->create(['fecha_inicio' => '2026-02-10']);
    $deMarzo = Novedad::factory()->for($this->contrato->empleado)->create(['fecha_inicio' => '2026-03-05']);

    ($this->liquidar)();

    expect($delPeriodo->fresh()->periodo_nomina_id)->toBe($this->periodo->id)
        ->and($delPeriodo->fresh()->estaLiquidada())->toBeTrue()
        ->and($deMarzo->fresh()->periodo_nomina_id)->toBeNull();
});

// Issue #14, criterio 5
test('al volver a liquidar no se duplican las horas extra del periodo', function () {
    Novedad::factory()->for($this->contrato->empleado)->create(['tipo' => TipoNovedad::HoraExtraDiurna, 'cantidad' => 2]);

    ($this->liquidar)();
    ($this->liquidar)();

    expect(valoresDelRecibo())->toHaveKey(ConceptoSistema::HoraExtraDiurna->value, '25000.00');
});
