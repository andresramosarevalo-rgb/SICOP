<?php

// Issue #141, criterio 1: casos CP-01 a CP-17 del plan de calidad de Nómina.

use App\Actions\Nomina\LiquidarPeriodoNomina;
use App\Enums\ConceptoSistema;
use App\Enums\EstadoPeriodo;
use App\Enums\PeriodicidadPago;
use App\Enums\Rol;
use App\Enums\TipoContrato;
use App\Enums\TipoNovedad;
use App\Mail\ReciboNominaMail;
use App\Models\Area;
use App\Models\Contrato;
use App\Models\Empleado;
use App\Models\Novedad;
use App\Models\PeriodoNomina;
use App\Models\ReciboNomina;
use App\Models\User;
use Database\Seeders\NominaSeeder;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * Casos de prueba CP-01 a CP-17 del plan de control de calidad (Taller 5).
 * Cada test cita el caso, la historia del plan y el issue con su criterio.
 */

beforeEach(function () {
    $this->auxiliar = User::factory()->conRol(Rol::AuxiliarNomina)->create();
});

/**
 * Valor de un concepto en el único recibo liquidado.
 */
function valorEnRecibo(ConceptoSistema $concepto): string
{
    return ReciboNomina::sole()->detalles()->whereHas('concepto', fn ($query) => $query->where('codigo', $concepto->value))
        ->value('valor_concepto') ?? '0.00';
}

/**
 * Liquida un periodo de febrero de 2026 para un único contrato con el salario y la periodicidad indicados.
 */
function liquidarUnContrato(string $salario, PeriodicidadPago $periodicidad = PeriodicidadPago::Mensual): ReciboNomina
{
    test()->seed(NominaSeeder::class);
    Contrato::factory()->create(['valor_salario_base' => $salario, 'periodicidad_pago' => $periodicidad]);
    $periodo = PeriodoNomina::factory()->create([
        'periodicidad_pago' => $periodicidad,
        'fecha_inicio' => '2026-02-01',
        'fecha_fin' => $periodicidad === PeriodicidadPago::Quincenal ? '2026-02-15' : '2026-02-28',
    ]);

    app(LiquidarPeriodoNomina::class)->ejecutar($periodo, User::factory()->create());

    return ReciboNomina::sole();
}

// HU-01 · CA1 (issue #8, criterio 3) · valores límite
test('CP-01 un contrato a término fijo con fecha de fin igual a la de inicio se rechaza', function () {
    $empleado = Empleado::factory()->create();

    $this->actingAs($this->auxiliar)
        ->post(route('nomina.empleados.contratos.store', $empleado), datosContrato([
            'tipo_contrato' => TipoContrato::TerminoFijo->value, 'fecha_inicio' => '2026-02-01', 'fecha_fin' => '2026-02-01',
        ]))
        ->assertSessionHasErrors(['fecha_fin' => 'La fecha de fin debe ser posterior a la fecha de inicio.']);

    expect(Contrato::count())->toBe(0);
});

// HU-01 · CA1 (issue #8, criterio 3) · valores límite
test('CP-02 un contrato a término fijo con fecha de fin un día después del inicio se registra', function () {
    $empleado = Empleado::factory()->create();

    $this->actingAs($this->auxiliar)
        ->post(route('nomina.empleados.contratos.store', $empleado), datosContrato([
            'tipo_contrato' => TipoContrato::TerminoFijo->value, 'fecha_inicio' => '2026-02-01', 'fecha_fin' => '2026-02-02',
        ]))
        ->assertSessionHasNoErrors();

    expect($empleado->contratoVigente()->sole()->fecha_fin->toDateString())->toBe('2026-02-02');
});

// HU-01 · CA2 (issue #6, criterio 2) · partición de equivalencias
test('CP-03 un documento ya registrado se rechaza y no crea el empleado', function () {
    Empleado::factory()->create(['numero_documento' => '1012345678']);

    $this->actingAs($this->auxiliar)
        ->post(route('nomina.empleados.store'), datosEmpleado(['area_id' => Area::factory()->create()->id]))
        ->assertSessionHasErrors(['numero_documento' => 'Ya existe un empleado con ese número de documento.']);

    expect(Empleado::count())->toBe(1);
});

// HU-01 · CA3 (issue #4, criterio 2) · partición de equivalencias
test('CP-04 un cajero que abre el módulo de empleados recibe 403', function () {
    $this->actingAs(User::factory()->conRol(Rol::Cajero)->create())
        ->get('/nomina/empleados')
        ->assertForbidden();
});

// HU-02 · CA1 (issue #12, criterio 1) · valores límite de 1 a 200 horas enteras; desde el issue #145 las
// horas extra de un día no pueden pasar de 2, así que 200 horas se rechazan por el máximo legal
test('CP-05 a CP-08 la cantidad de horas extra debe estar entre 1 y 200', function (int $horas, bool $seRegistra) {
    $respuesta = $this->actingAs($this->auxiliar)->post(route('nomina.novedades.store'), [
        'empleado_id' => Empleado::factory()->create()->id,
        'tipo' => TipoNovedad::HoraExtraDiurna->value,
        'fecha_inicio' => '2026-02-10',
        'cantidad' => $horas,
    ]);

    $seRegistra ? $respuesta->assertSessionHasNoErrors() : $respuesta->assertSessionHasErrors('cantidad');
    expect(Novedad::count())->toBe($seRegistra ? 1 : 0);
})->with([
    'CP-05 · 0 horas' => [0, false],
    'CP-06 · 1 hora' => [1, true],
    'CP-07 · 200 horas' => [200, false],
    'CP-08 · 201 horas' => [201, false],
]);

// HU-02 · CA2 (issue #12, criterio 4) · partición de equivalencias
test('CP-09 una novedad ya liquidada no se puede modificar', function () {
    $novedad = Novedad::factory()->liquidada()->create();

    $this->actingAs($this->auxiliar)
        ->put(route('nomina.novedades.update', $novedad), [
            'empleado_id' => $novedad->empleado_id,
            'tipo' => TipoNovedad::HoraExtraDiurna->value,
            'fecha_inicio' => '2026-02-10',
            'cantidad' => 8,
        ])
        ->assertForbidden();

    expect($novedad->fresh()->cantidad)->toBe(2);
});

// HU-03 · CA1 (issue #13, criterio 2) · valores límite, regla R1
test('CP-10 con 2 SMMLV y 30 días se paga el auxilio de transporte completo', function () {
    liquidarUnContrato('3501810');

    expect(valorEnRecibo(ConceptoSistema::AuxilioTransporte))->toBe('249095.00');
});

// HU-03 · CA1 (issue #13, criterio 2) · valores límite, regla R3
test('CP-11 con 2 SMMLV más un peso no se paga auxilio de transporte', function () {
    liquidarUnContrato('3501811');

    expect(valorEnRecibo(ConceptoSistema::AuxilioTransporte))->toBe('0.00');
});

// HU-03 · CA1 (issue #13, criterio 3) · tabla de decisión, regla R2
test('CP-12 con 1 SMMLV y 15 días laborados el auxilio es el 50 %', function () {
    $recibo = liquidarUnContrato('1750905', PeriodicidadPago::Quincenal);

    expect($recibo->dias_liquidados)->toBe(15)
        ->and(valorEnRecibo(ConceptoSistema::AuxilioTransporte))->toBe('124548.00');
});

// HU-03 · CA2 (issue #15, criterio 1) · valores límite, regla R4
test('CP-13 con 4 SMMLV se descuentan salud 4 %, pensión 4 % y fondo de solidaridad 1 %', function () {
    liquidarUnContrato('7003620');

    expect(valorEnRecibo(ConceptoSistema::Salud))->toBe('280145.00')
        ->and(valorEnRecibo(ConceptoSistema::Pension))->toBe('280145.00')
        ->and(valorEnRecibo(ConceptoSistema::FondoSolidaridadPensional))->toBe('70036.00');
});

// HU-03 · CA2 (issue #15, criterio 1) · valores límite, regla R3
test('CP-14 con 4 SMMLV menos un peso solo se descuentan salud y pensión', function () {
    liquidarUnContrato('7003619');

    expect(valorEnRecibo(ConceptoSistema::Salud))->toBe('280145.00')
        ->and(valorEnRecibo(ConceptoSistema::FondoSolidaridadPensional))->toBe('0.00');
});

// HU-03 · CA3 (issue #13, criterio 1) · partición de equivalencias
test('CP-15 con 3 SMMLV el neto es el devengado menos las deducciones redondeadas al peso', function () {
    $recibo = liquidarUnContrato('5252715');

    // 5.252.715 − 210.109 de salud − 210.109 de pensión. El plan original esperaba 4.832.498
    // (3 SMMLV × 0,92), pero cada deducción se redondea por separado.
    expect($recibo->valor_total_devengado)->toBe('5252715.00')
        ->and($recibo->valor_total_deducciones)->toBe('420218.00')
        ->and($recibo->valor_neto)->toBe('4832497.00');
});

// HU-04 · CA1 (issue #16, criterio 3) · partición de equivalencias
test('CP-16 el recibo de una liquidación cerrada muestra el mismo neto liquidado', function () {
    $recibo = liquidarUnContrato('1750905');
    $recibo->periodo->forceFill(['estado' => EstadoPeriodo::Cerrado])->save();

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.recibos.show', $recibo))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Nomina/Recibos/Show')
            ->where('recibo.valor_neto', $recibo->valor_neto)
            ->where('recibo.valor_neto', '1859928.00'));
});

// HU-04 · CA2 (issue #17, criterio 2) · partición de equivalencias
test('CP-17 una liquidación que no está cerrada no permite enviar el recibo', function () {
    Mail::fake();
    $recibo = liquidarUnContrato('1750905');

    $this->actingAs($this->auxiliar)
        ->post(route('nomina.periodos.enviar', $recibo->periodo))
        ->assertSessionHasErrors(['periodo' => 'Solo se pueden enviar los recibos de un periodo cerrado.']);

    Mail::assertNotQueued(ReciboNominaMail::class);
});
