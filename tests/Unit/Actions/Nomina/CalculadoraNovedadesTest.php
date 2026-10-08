<?php

use App\Actions\Nomina\CalculadoraNomina;
use App\Actions\Nomina\ResultadoLiquidacion;
use App\Enums\ConceptoSistema;
use App\Enums\PeriodicidadPago;
use App\Enums\TipoNovedad;
use App\Models\Contrato;
use App\Models\Novedad;
use App\Models\ParametroNomina;
use App\Models\PeriodoNomina;
use Database\Seeders\NominaSeeder;

/**
 * Novedad sin guardar del tipo indicado.
 *
 * @param  array<string, mixed>  $atributos
 */
function novedad(TipoNovedad $tipo, array $atributos = []): Novedad
{
    return new Novedad(['tipo' => $tipo, 'fecha_inicio' => '2026-02-10', ...$atributos]);
}

/**
 * Liquida un mes de febrero de 2026 con el salario y las novedades indicadas.
 *
 * @param  list<Novedad>  $novedades
 */
function liquidarFebrero(string $salario, array $novedades): ResultadoLiquidacion
{
    return (new CalculadoraNomina)->calcular(
        new Contrato(['valor_salario_base' => $salario, 'periodicidad_pago' => PeriodicidadPago::Mensual]),
        new ParametroNomina(NominaSeeder::PARAMETROS_2026),
        new PeriodoNomina(['periodicidad_pago' => PeriodicidadPago::Mensual, 'fecha_inicio' => '2026-02-01', 'fecha_fin' => '2026-02-28']),
        $novedades,
    );
}

// Issue #14, criterio 1
test('las horas extra pagan la hora más el recargo y los recargos pagan solo el recargo', function (TipoNovedad $tipo, ConceptoSistema $concepto, int $horas, string $valor) {
    // Salario de 2.100.000 entre 210 horas: la hora vale 10.000.
    $resultado = liquidarFebrero('2100000', [novedad($tipo, ['cantidad' => $horas])]);

    expect((string) $resultado->valorDe($concepto->value))->toBe($valor);
})->with([
    '2 horas extra diurnas al 125 %' => [TipoNovedad::HoraExtraDiurna, ConceptoSistema::HoraExtraDiurna, 2, '25000'],
    '1 hora extra nocturna al 175 %' => [TipoNovedad::HoraExtraNocturna, ConceptoSistema::HoraExtraNocturna, 1, '17500'],
    '2 horas de recargo nocturno al 35 %' => [TipoNovedad::RecargoNocturno, ConceptoSistema::RecargoNocturno, 2, '7000'],
    '8 horas dominicales al 90 %' => [TipoNovedad::DominicalFestivo, ConceptoSistema::RecargoDominicalFestivo, 8, '72000'],
]);

// Issue #14, criterio 1
test('las horas extra hacen parte de la base de salud y pensión', function () {
    $resultado = liquidarFebrero('2100000', [novedad(TipoNovedad::HoraExtraDiurna, ['cantidad' => 2])]);

    expect((string) $resultado->valorDe(ConceptoSistema::Salud->value))->toBe('85000');
});

// Issue #14, criterio 2
test('una falta descuenta un día de salario y de auxilio de transporte', function () {
    $resultado = liquidarFebrero('1500000', [novedad(TipoNovedad::Falta)]);

    expect((string) $resultado->valorDe(ConceptoSistema::Salario->value))->toBe('1450000')
        ->and((string) $resultado->valorDe(ConceptoSistema::AuxilioTransporte->value))->toBe('240792')
        ->and($resultado->diasLiquidados)->toBe(29);
});

test('un retardo descuenta los minutos al valor de la hora', function () {
    $resultado = liquidarFebrero('2100000', [novedad(TipoNovedad::Retardo, ['cantidad' => 30])]);

    expect((string) $resultado->valorDe(ConceptoSistema::DescuentoRetardo->value))->toBe('5000');
});

// Issue #14, criterio 3
test('la incapacidad se paga al porcentaje parametrizado sin bajar del salario mínimo diario', function (string $salario, string $valor) {
    $resultado = liquidarFebrero($salario, [novedad(TipoNovedad::Incapacidad, ['fecha_fin' => '2026-02-12'])]);

    expect((string) $resultado->valorDe(ConceptoSistema::Incapacidad->value))->toBe($valor)
        ->and($resultado->diasLiquidados)->toBe(27);
})->with([
    'salario mínimo: se paga el mínimo diario' => ['1750905', '175091'],
    'tres millones: 66,67 % del salario diario' => ['3000000', '200010'],
]);

// Issue #14, criterio 3
test('una incapacidad que empieza en el mes anterior solo cuenta los días dentro del periodo', function () {
    $resultado = liquidarFebrero('3000000', [novedad(TipoNovedad::Incapacidad, ['fecha_inicio' => '2026-01-28', 'fecha_fin' => '2026-02-03'])]);

    expect((string) $resultado->valorDe(ConceptoSistema::Incapacidad->value))->toBe('200010')
        ->and($resultado->diasLiquidados)->toBe(27);
});

test('las vacaciones se pagan a salario base en su propia línea', function () {
    $resultado = liquidarFebrero('3000000', [novedad(TipoNovedad::Vacaciones, ['fecha_fin' => '2026-02-14'])]);

    expect((string) $resultado->valorDe(ConceptoSistema::Vacaciones->value))->toBe('500000')
        ->and((string) $resultado->valorDe(ConceptoSistema::Salario->value))->toBe('2500000');
});
