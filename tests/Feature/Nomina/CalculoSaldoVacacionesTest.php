<?php

use App\Actions\Nomina\CalcularSaldoVacaciones;
use App\Enums\TipoNovedad;
use App\Models\Contrato;
use App\Models\Empleado;
use App\Models\Novedad;
use Carbon\CarbonImmutable;

beforeEach(function () {
    config([
        'vacaciones.dias_habiles' => [1, 2, 3, 4, 5],
        'vacaciones.festivos' => [],
        // Calendario de referencia explícito para estas pruebas de intervalos.
        'vacaciones.excluir_festivos_colombianos' => false,
        'vacaciones.es_calendario_confirmado' => true,
    ]);
    $this->empleado = Empleado::factory()->create();
});

// Issue #188, criterio 1: años completos, proporción real, bisiestos y límites inclusivos.
test('causa vacaciones según el tiempo real de servicio', function (string $inicio, string $corte, string $esperado) {
    Contrato::factory()->for($this->empleado)->create(['fecha_inicio' => $inicio]);
    $saldo = app(CalcularSaldoVacaciones::class)->ejecutar($this->empleado, CarbonImmutable::parse($corte));

    expect($saldo['diasCausados'])->toBe($esperado)
        ->and($saldo['diasUtilizados'])->toBe(0)
        ->and($saldo['diasDisponibles'])->toBe($esperado);
})->with([
    'un año' => ['2025-01-01', '2025-12-31', '15.0000'],
    'varios años' => ['2023-01-01', '2025-12-31', '45.0000'],
    'año bisiesto' => ['2024-01-01', '2024-12-31', '15.0000'],
    'fracción de año' => ['2025-01-01', '2025-06-30', '7.4384'],
    'ingreso el 29 de febrero' => ['2024-02-29', '2025-02-27', '15.0000'],
    'aniversario bisiesto sin deriva' => ['2024-02-29', '2028-02-28', '60.0000'],
    'primer día' => ['2026-05-04', '2026-05-04', '0.0411'],
    'contrato futuro' => ['2027-01-01', '2026-12-31', '0.0000'],
]);

// Issue #188, criterio 1: no se utiliza created_at como fecha de ingreso.
test('sin contratos no inventa antigüedad a partir del registro del empleado', function () {
    $this->empleado->forceFill(['created_at' => '2000-01-01'])->save();
    $saldo = app(CalcularSaldoVacaciones::class)->ejecutar($this->empleado, CarbonImmutable::parse('2026-12-31'));

    expect($saldo['fechaIngreso'])->toBeNull()
        ->and($saldo['diasCausados'])->toBe('0.0000')
        ->and($saldo['diasDisponibles'])->toBe('0.0000')
        ->and($saldo['advertencias'])->not->toBeEmpty();
});

// Issue #188, criterios 1 y 2: continuidad y solapamientos no duplican servicio.
test('une contratos contiguos o solapados antes de causar', function (string $finAnterior, string $inicioNuevo) {
    Contrato::factory()->for($this->empleado)->create([
        'fecha_inicio' => '2025-01-01', 'fecha_fin' => $finAnterior, 'es_vigente' => false,
    ]);
    Contrato::factory()->for($this->empleado)->create(['fecha_inicio' => $inicioNuevo]);
    $saldo = app(CalcularSaldoVacaciones::class)->ejecutar($this->empleado, CarbonImmutable::parse('2025-12-31'));

    expect($saldo['fechaIngreso'])->toBe('2025-01-01')
        ->and($saldo['diasCausados'])->toBe('15.0000');
})->with([
    'renovación contigua' => ['2025-06-30', '2025-07-01'],
    'históricos solapados' => ['2025-09-30', '2025-07-01'],
]);

// Issue #188, criterio 1: los huecos laborales no causan días.
test('suma únicamente los intervalos de servicio y respeta la terminación', function () {
    Contrato::factory()->for($this->empleado)->create([
        'fecha_inicio' => '2025-01-01', 'fecha_fin' => '2025-01-31', 'es_vigente' => false,
    ]);
    Contrato::factory()->for($this->empleado)->create([
        'fecha_inicio' => '2025-03-01', 'fecha_fin' => '2025-12-31', 'es_vigente' => false,
    ]);
    $saldo = app(CalcularSaldoVacaciones::class)->ejecutar($this->empleado, CarbonImmutable::parse('2026-12-31'));

    expect($saldo['diasCausados'])->toBe('13.8493');
});

// Issue #188, criterios 1 y 4: no inferir una terminación de datos incompletos.
test('advierte contratos históricos ambiguos o con fechas invertidas', function (?string $fin) {
    Contrato::factory()->for($this->empleado)->create([
        'fecha_inicio' => '2025-01-01', 'fecha_fin' => $fin, 'es_vigente' => false,
    ]);
    $saldo = app(CalcularSaldoVacaciones::class)->ejecutar($this->empleado, CarbonImmutable::parse('2026-12-31'));

    expect($saldo['fechaIngreso'])->toBeNull()
        ->and($saldo['diasCausados'])->toBe('0.0000')
        ->and(count($saldo['advertencias']))->toBeGreaterThan(1);
})->with([null, '2024-12-31']);

// Issue #188, criterio 2: fines de semana, festivos configurados y solapamientos.
test('cuenta cada día hábil una sola vez y conserva un saldo negativo', function () {
    config(['vacaciones.festivos' => ['2026-01-12']]);
    Contrato::factory()->for($this->empleado)->create(['fecha_inicio' => '2026-01-01']);
    foreach ([['2026-01-08', '2026-01-12'], ['2026-01-09', '2026-01-13'], ['2026-01-08', '2026-01-12']] as [$inicio, $fin]) {
        Novedad::factory()->for($this->empleado)->create([
            'tipo' => TipoNovedad::Vacaciones, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
        ]);
    }
    Novedad::factory()->for($this->empleado)->create([
        'tipo' => TipoNovedad::Incapacidad, 'fecha_inicio' => '2026-01-14', 'fecha_fin' => '2026-01-15',
    ]);
    $saldo = app(CalcularSaldoVacaciones::class)->ejecutar($this->empleado, CarbonImmutable::parse('2026-01-31'));

    expect($saldo['diasUtilizados'])->toBe(3)
        ->and($saldo['diasCausados'])->toBe('1.2740')
        ->and($saldo['diasDisponibles'])->toBe('-1.7260');
});

// Issue #188, criterio 2: recortar cada novedad al servicio conocido y al corte.
test('excluye consumos anteriores al ingreso, posteriores al retiro, en huecos y futuros', function () {
    Contrato::factory()->for($this->empleado)->create([
        'fecha_inicio' => '2026-01-07', 'fecha_fin' => '2026-01-09', 'es_vigente' => false,
    ]);
    Contrato::factory()->for($this->empleado)->create(['fecha_inicio' => '2026-01-14']);
    Novedad::factory()->for($this->empleado)->create([
        'tipo' => TipoNovedad::Vacaciones, 'fecha_inicio' => '2025-12-01', 'fecha_fin' => '2026-02-28',
    ]);
    $saldo = app(CalcularSaldoVacaciones::class)->ejecutar($this->empleado, CarbonImmutable::parse('2026-01-15'));

    expect($saldo['diasUtilizados'])->toBe(5);
});

// Issue #188, criterio 2: fecha_fin nula representa un día; rangos inválidos no consumen.
test('trata novedades de un día y no cuenta intervalos invertidos', function () {
    Contrato::factory()->for($this->empleado)->create();
    foreach ([['2026-01-09', null], ['2026-01-10', null], ['2026-01-20', '2026-01-19']] as [$inicio, $fin]) {
        Novedad::factory()->for($this->empleado)->create([
            'tipo' => TipoNovedad::Vacaciones, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
        ]);
    }
    $saldo = app(CalcularSaldoVacaciones::class)->ejecutar($this->empleado, CarbonImmutable::parse('2026-01-31'));

    expect($saldo['diasUtilizados'])->toBe(1);
});

// Issue #188, criterio 2: la jornada es configurable, sin deducirla de horas_mensuales.
test('aplica la jornada configurada incluyendo sábado si corresponde', function () {
    config(['vacaciones.dias_habiles' => [1, 2, 3, 4, 5, 6]]);
    Contrato::factory()->for($this->empleado)->create();
    Novedad::factory()->for($this->empleado)->create([
        'tipo' => TipoNovedad::Vacaciones, 'fecha_inicio' => '2026-01-09', 'fecha_fin' => '2026-01-11',
    ]);
    $saldo = app(CalcularSaldoVacaciones::class)->ejecutar($this->empleado, CarbonImmutable::parse('2026-01-31'));

    expect($saldo['diasUtilizados'])->toBe(2);
});

// Issue #188, criterio 4: consultas repetidas no usan relaciones antiguas ni modifican fechas.
test('recalcula con el mismo empleado aunque sus relaciones estén precargadas', function () {
    $contrato = Contrato::factory()->for($this->empleado)->create();
    $this->empleado->load(['contratos', 'novedades']);
    $calculadora = app(CalcularSaldoVacaciones::class);
    $corte = CarbonImmutable::parse('2026-12-31');
    $primero = $calculadora->ejecutar($this->empleado, $corte);
    Novedad::factory()->for($this->empleado)->create([
        'tipo' => TipoNovedad::Vacaciones, 'fecha_inicio' => '2026-01-09', 'fecha_fin' => null,
    ]);
    $segundo = $calculadora->ejecutar($this->empleado, $corte);
    $contrato->update(['fecha_inicio' => '2025-01-01']);
    $tercero = $calculadora->ejecutar($this->empleado, $corte);

    expect($primero['diasDisponibles'])->toBe('15.0000')
        ->and($segundo['diasDisponibles'])->toBe('14.0000')
        ->and($tercero['diasDisponibles'])->toBe('29.0000')
        ->and($calculadora->ejecutar($this->empleado, $corte))->toBe($tercero)
        ->and($this->empleado->contratos->first()->fecha_inicio->toDateString())->toBe('2026-01-01')
        ->and($corte->toDateString())->toBe('2026-12-31');
});

// Issue #188, criterio 3: las limitaciones se entregan en el expediente.
test('expone que el calendario provisional y el historial no están certificados', function () {
    config(['vacaciones.es_calendario_confirmado' => false]);
    Contrato::factory()->for($this->empleado)->create();
    $saldo = app(CalcularSaldoVacaciones::class)->ejecutar($this->empleado, CarbonImmutable::parse('2026-12-31'));

    expect($saldo['criterioCalendario'])->not->toBeEmpty()
        ->and(count($saldo['advertencias']))->toBeGreaterThanOrEqual(2);
});

// Issue #188, criterio 1: la fecha_fin limita el servicio aunque siga marcado vigente.
test('respeta la terminación de un contrato todavía marcado vigente', function () {
    Contrato::factory()->for($this->empleado)->create([
        'fecha_inicio' => '2025-01-01', 'fecha_fin' => '2025-12-31', 'es_vigente' => true,
    ]);
    $saldo = app(CalcularSaldoVacaciones::class)->ejecutar($this->empleado, CarbonImmutable::parse('2026-12-31'));

    expect($saldo['diasCausados'])->toBe('15.0000');
});

// Issue #188, criterios 1 y 3: el corte implícito corresponde al día local de Colombia.
test('usa la fecha de Bogotá cerca de medianoche UTC', function () {
    $this->travelTo(CarbonImmutable::parse('2027-01-01 02:00:00', 'UTC'));
    Contrato::factory()->for($this->empleado)->create(['fecha_inicio' => '2026-01-01']);
    $saldo = app(CalcularSaldoVacaciones::class)->ejecutar($this->empleado);

    expect($saldo['fechaCorte'])->toBe('2026-12-31')
        ->and($saldo['diasCausados'])->toBe('15.0000');
});
