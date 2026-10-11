<?php

use App\Actions\Nomina\CalculadoraNomina;
use App\Actions\Nomina\ResultadoLiquidacion;
use App\Enums\ConceptoSistema;
use App\Enums\PeriodicidadPago;
use App\Models\Contrato;
use App\Models\ParametroNomina;
use App\Models\PeriodoNomina;
use Database\Seeders\NominaSeeder;

/**
 * Liquida un periodo de febrero de 2026 sin novedades para el salario y la periodicidad indicados.
 */
function liquidarSalario(string $salario, PeriodicidadPago $periodicidad = PeriodicidadPago::Mensual): ResultadoLiquidacion
{
    $fin = $periodicidad === PeriodicidadPago::Quincenal ? '2026-02-15' : '2026-02-28';

    return (new CalculadoraNomina)->calcular(
        new Contrato(['valor_salario_base' => $salario, 'periodicidad_pago' => $periodicidad]),
        new ParametroNomina(NominaSeeder::PARAMETROS_2026),
        new PeriodoNomina(['periodicidad_pago' => $periodicidad, 'fecha_inicio' => '2026-02-01', 'fecha_fin' => $fin]),
    );
}

// Issue #15, criterio 1
test('el Fondo de Solidaridad Pensional se descuenta desde cuatro salarios mínimos', function (string $salario, string $fondo) {
    expect((string) liquidarSalario($salario)->valorDe(ConceptoSistema::FondoSolidaridadPensional->value))->toBe($fondo);
})->with([
    'un peso menos de 4 SMMLV' => ['7003619', '0'],
    'exactamente 4 SMMLV: 1 %' => ['7003620', '70036'],
    '16 SMMLV: 1,2 %' => ['28014480', '336174'],
    'más de 20 SMMLV: 2 %' => ['40000000', '800000'],
]);

// Issue #15, criterio 2
test('sin base depurada mayor a 95 UVT no hay retención en la fuente', function () {
    $resultado = liquidarSalario('3000000');

    expect((string) $resultado->valorDe(ConceptoSistema::RetencionFuente->value))->toBe('0');
});

// Issue #15, criterio 3
test('la retención aplica la franja del art. 383 y se redondea al múltiplo de mil', function (string $salario, string $retencion) {
    expect((string) liquidarSalario($salario)->valorDe(ConceptoSistema::RetencionFuente->value))->toBe($retencion);
})->with([
    // 10.000.000 − 900.000 de aportes − 25 % exento = 6.825.000 = 130,31 UVT → (130,31 − 95) × 19 %.
    'franja del 19 %' => ['10000000', '351000'],
    // 20.000.000 − 1.800.000 − 3.447.955 (tope de renta exenta) = 281,67 UVT → (281,67 − 150) × 28 % + 10.
    'franja del 28 % con tope de renta exenta' => ['20000000', '2455000'],
]);

// Issue #15, criterio 3
test('en una quincena el FSP y la retención se calculan sobre el equivalente mensual', function () {
    $resultado = liquidarSalario('10000000', PeriodicidadPago::Quincenal);

    expect((string) $resultado->valorDe(ConceptoSistema::FondoSolidaridadPensional->value))->toBe('50000')
        ->and((string) $resultado->valorDe(ConceptoSistema::RetencionFuente->value))->toBe('176000');
});
