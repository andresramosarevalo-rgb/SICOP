<?php

use App\Actions\Nomina\LiquidarPeriodoNomina;
use App\Models\Contrato;
use App\Models\PeriodoNomina;
use App\Models\ReciboNomina;
use App\Models\User;
use Database\Seeders\NominaSeeder;

beforeEach(function () {
    $this->seed(NominaSeeder::class);
});

// Issue #15, criterios 1 y 3
test('el recibo de un salario alto incluye FSP y retención en la fuente', function () {
    Contrato::factory()->create(['valor_salario_base' => '10000000']);

    app(LiquidarPeriodoNomina::class)->ejecutar(PeriodoNomina::factory()->create(), User::factory()->create());

    $recibo = ReciboNomina::sole();
    $valores = $recibo->detalles()->with('concepto')->get()
        ->mapWithKeys(fn ($detalle) => [$detalle->concepto->codigo => $detalle->valor_concepto]);

    expect($valores->only(['FSP', 'RETENCION'])->all())->toBe(['FSP' => '100000.00', 'RETENCION' => '351000.00'])
        ->and($recibo->valor_total_deducciones)->toBe('1251000.00')
        ->and($recibo->valor_neto)->toBe('8749000.00');
});

// Issue #15, criterio 2
test('el recibo de un salario mínimo no tiene FSP ni retención', function () {
    Contrato::factory()->create(['valor_salario_base' => '1750905']);

    app(LiquidarPeriodoNomina::class)->ejecutar(PeriodoNomina::factory()->create(), User::factory()->create());

    $codigos = ReciboNomina::sole()->detalles()->with('concepto')->get()->pluck('concepto.codigo');

    expect($codigos)->not->toContain('FSP')->not->toContain('RETENCION');
});
