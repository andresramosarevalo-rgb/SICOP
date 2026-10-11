<?php

use App\Actions\Nomina\CalculadoraNomina;
use App\Actions\Nomina\ResultadoLiquidacion;
use App\Enums\ConceptoSistema;
use App\Enums\FormaCalculo;
use App\Enums\PeriodicidadPago;
use App\Enums\TipoConcepto;
use App\Enums\TipoNovedad;
use App\Models\AsignacionConcepto;
use App\Models\ConceptoNomina;
use App\Models\Contrato;
use App\Models\Novedad;
use App\Models\ParametroNomina;
use App\Models\PeriodoNomina;
use Database\Seeders\NominaSeeder;

/**
 * Concepto sin guardar.
 *
 * @param  array<string, mixed>  $atributos
 */
function concepto(string $codigo, TipoConcepto $tipo, array $atributos = []): ConceptoNomina
{
    return new ConceptoNomina([
        'codigo' => $codigo, 'nombre' => $codigo, 'tipo' => $tipo,
        'forma_calculo' => FormaCalculo::ValorFijo, 'es_constitutivo_salario' => false, ...$atributos,
    ]);
}

/**
 * Asignación sin guardar del concepto indicado.
 *
 * @param  array<string, mixed>  $atributos
 */
function asignacion(ConceptoNomina $concepto, array $atributos = []): AsignacionConcepto
{
    return (new AsignacionConcepto(['fecha_inicio' => '2026-01-01', ...$atributos]))->setRelation('concepto', $concepto);
}

/**
 * Liquida febrero de 2026 para un salario de 3.000.000 con las novedades y asignaciones indicadas.
 *
 * @param  list<Novedad>  $novedades
 * @param  list<AsignacionConcepto>  $asignaciones
 */
function liquidarConConceptos(array $novedades, array $asignaciones): ResultadoLiquidacion
{
    return (new CalculadoraNomina)->calcular(
        new Contrato(['valor_salario_base' => '3000000', 'periodicidad_pago' => PeriodicidadPago::Mensual]),
        new ParametroNomina(NominaSeeder::PARAMETROS_2026),
        new PeriodoNomina(['periodicidad_pago' => PeriodicidadPago::Mensual, 'fecha_inicio' => '2026-02-01', 'fecha_fin' => '2026-02-28']),
        $novedades,
        $asignaciones,
    );
}

// Issue #14, criterio 4
test('un concepto recurrente vigente aparece como devengo o deducción', function () {
    $resultado = liquidarConConceptos([], [
        asignacion(concepto('BONO', TipoConcepto::Devengo, ['valor_base' => '100000']), ['valor_asignado' => '80000']),
        asignacion(concepto('LIBRANZA', TipoConcepto::Deduccion, ['forma_calculo' => FormaCalculo::Porcentaje, 'porcentaje_base' => '10'])),
    ]);

    expect((string) $resultado->valorDe('BONO'))->toBe('80000')
        ->and((string) $resultado->valorDe('LIBRANZA'))->toBe('300000')
        ->and((string) $resultado->neto())->toBe('2789095');
});

// Issue #14, criterio 4
test('un concepto asignado sin valor propio usa el valor del concepto', function () {
    $resultado = liquidarConConceptos([], [asignacion(concepto('BONO', TipoConcepto::Devengo, ['valor_base' => '100000']))]);

    expect((string) $resultado->valorDe('BONO'))->toBe('100000');
});

// Issue #14, criterio 4
test('un concepto asignado fuera de su vigencia no se aplica', function (array $vigencia) {
    $resultado = liquidarConConceptos([], [asignacion(concepto('BONO', TipoConcepto::Devengo, ['valor_base' => '100000']), $vigencia)]);

    expect((string) $resultado->valorDe('BONO'))->toBe('0');
})->with([
    'terminó antes del periodo' => [['fecha_inicio' => '2025-01-01', 'fecha_fin' => '2026-01-31']],
    'empieza después del periodo' => [['fecha_inicio' => '2026-03-01']],
]);

// Issue #14, criterio 4
test('un bono constitutivo de salario suma a la base de aportes', function () {
    $resultado = liquidarConConceptos([], [
        asignacion(concepto('BONO', TipoConcepto::Devengo, ['valor_base' => '500000', 'es_constitutivo_salario' => true])),
    ]);

    expect((string) $resultado->valorDe(ConceptoSistema::Salud->value))->toBe('140000');
});

// Issue #14, criterio 4
test('un concepto eventual del periodo aparece en el recibo', function () {
    $eventual = (new Novedad(['tipo' => TipoNovedad::ConceptoEventual, 'fecha_inicio' => '2026-02-20', 'valor_eventual' => '45000']))
        ->setRelation('concepto', concepto('PRESTAMO', TipoConcepto::Deduccion));

    $resultado = liquidarConConceptos([$eventual], []);

    expect((string) $resultado->valorDe('PRESTAMO'))->toBe('45000')
        ->and((string) $resultado->totalDeducciones())->toBe('285000');
});
