<?php

use App\Actions\Nomina\RegistrarContrato;
use App\Enums\PeriodicidadPago;
use App\Enums\TipoContrato;
use App\Models\Contrato;
use App\Models\Empleado;

/**
 * Datos validados de un contrato nuevo.
 *
 * @return array<string, mixed>
 */
function contratoNuevo(string $fechaInicio): array
{
    return [
        'tipo_contrato' => TipoContrato::TerminoIndefinido->value,
        'periodicidad_pago' => PeriodicidadPago::Mensual->value,
        'fecha_inicio' => $fechaInicio,
        'valor_salario_base' => '2000000',
    ];
}

// Issue #8, criterio 1
test('el contrato de un empleado sin contrato vigente queda vigente', function () {
    $empleado = Empleado::factory()->create();

    $contrato = (new RegistrarContrato)->ejecutar($empleado, contratoNuevo('2026-02-01'));

    expect($contrato->es_vigente)->toBeTrue()
        ->and($empleado->contratoVigente()->sole()->id)->toBe($contrato->id);
});

// Issue #8, criterio 2
test('el contrato vigente anterior se cierra el día antes del nuevo y queda uno solo vigente', function () {
    $empleado = Empleado::factory()->create();
    $anterior = Contrato::factory()->for($empleado)->create(['fecha_inicio' => '2026-01-01']);

    (new RegistrarContrato)->ejecutar($empleado, contratoNuevo('2026-07-01'));

    expect($anterior->fresh()->es_vigente)->toBeFalse()
        ->and($anterior->fresh()->fecha_fin->toDateString())->toBe('2026-06-30')
        ->and($empleado->contratos()->where('es_vigente', true)->count())->toBe(1);
});

// Issue #8, criterio 2
test('un contrato a término fijo que ya terminó conserva su fecha de fin al cerrarse', function () {
    $empleado = Empleado::factory()->create();
    $anterior = Contrato::factory()->for($empleado)->create([
        'tipo_contrato' => TipoContrato::TerminoFijo,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-03-31',
    ]);

    (new RegistrarContrato)->ejecutar($empleado, contratoNuevo('2026-05-01'));

    expect($anterior->fresh()->fecha_fin->toDateString())->toBe('2026-03-31');
});
