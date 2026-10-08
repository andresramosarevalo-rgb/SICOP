<?php

use App\Actions\Nomina\CalculadoraNomina;
use App\Enums\ConceptoSistema;
use App\Enums\PeriodicidadPago;
use App\Models\Contrato;
use App\Models\ParametroNomina;
use Database\Seeders\NominaSeeder;

/**
 * Contrato sin guardar con el salario y la periodicidad indicados.
 */
function contratoDe(string $salario, PeriodicidadPago $periodicidad = PeriodicidadPago::Mensual): Contrato
{
    return new Contrato(['valor_salario_base' => $salario, 'periodicidad_pago' => $periodicidad]);
}

beforeEach(function () {
    $this->parametros = new ParametroNomina(NominaSeeder::PARAMETROS_2026);
});

// Issue #13, criterio 1
test('un salario mínimo mensual recibe auxilio de transporte y paga salud y pensión del 4 %', function () {
    $resultado = (new CalculadoraNomina)->calcular(contratoDe('1750905'), $this->parametros);

    expect((string) $resultado->valorDe(ConceptoSistema::Salario->value))->toBe('1750905')
        ->and((string) $resultado->valorDe(ConceptoSistema::AuxilioTransporte->value))->toBe('249095')
        ->and((string) $resultado->valorDe(ConceptoSistema::Salud->value))->toBe('70036')
        ->and((string) $resultado->valorDe(ConceptoSistema::Pension->value))->toBe('70036')
        ->and((string) $resultado->totalDevengado())->toBe('2000000')
        ->and((string) $resultado->totalDeducciones())->toBe('140072')
        ->and((string) $resultado->neto())->toBe('1859928')
        ->and($resultado->diasLiquidados)->toBe(30);
});

// Issue #13, criterio 2
test('el auxilio de transporte solo se paga hasta dos salarios mínimos', function (string $salario, bool $tieneAuxilio) {
    $resultado = (new CalculadoraNomina)->calcular(contratoDe($salario), $this->parametros);

    expect($resultado->valorDe(ConceptoSistema::AuxilioTransporte->value) > 0)->toBe($tieneAuxilio);
})->with([
    'exactamente dos salarios mínimos' => ['3501810', true],
    'un peso por encima' => ['3501811', false],
    'tres millones y medio largo' => ['4000000', false],
]);

// Issue #13, criterio 3
test('una quincena liquida 15 de 30 días del salario y del auxilio', function () {
    $resultado = (new CalculadoraNomina)->calcular(contratoDe('2000000', PeriodicidadPago::Quincenal), $this->parametros);

    expect((string) $resultado->valorDe(ConceptoSistema::Salario->value))->toBe('1000000')
        ->and((string) $resultado->valorDe(ConceptoSistema::AuxilioTransporte->value))->toBe('124548')
        ->and((string) $resultado->valorDe(ConceptoSistema::Salud->value))->toBe('40000')
        ->and($resultado->diasLiquidados)->toBe(15);
});

test('una semana liquida 7 de 30 días del salario', function () {
    $resultado = (new CalculadoraNomina)->calcular(contratoDe('3000000', PeriodicidadPago::Semanal), $this->parametros);

    expect((string) $resultado->valorDe(ConceptoSistema::Salario->value))->toBe('700000')
        ->and($resultado->diasLiquidados)->toBe(7);
});
