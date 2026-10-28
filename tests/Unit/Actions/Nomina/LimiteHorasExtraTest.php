<?php

use App\Actions\Nomina\LimiteHorasExtra;
use App\Enums\TipoNovedad;
use App\Models\Empleado;
use App\Models\Novedad;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->empleado = Empleado::factory()->create();
    $this->limite = new LimiteHorasExtra;

    // El 10 de febrero de 2026 es martes: su semana va del 9 al 15 de febrero.
    $this->motivo = fn (int $horas, string $fecha = '2026-02-10', ?Novedad $editada = null) => $this->limite
        ->motivoDeRechazo($this->empleado->id, CarbonImmutable::parse($fecha), $horas, $editada);
});

// Issue #158, criterio 2
test('exactamente 2 horas en el día caben y la hora 3 se rechaza', function () {
    expect(($this->motivo)(2))->toBeNull()
        ->and(($this->motivo)(3))->toBe('Supera el máximo de 2 horas extra por día; al empleado le quedan 2 horas ese día.');
});

// Issue #158, criterio 2
test('las horas diurnas y nocturnas del día se suman', function () {
    Novedad::factory()->for($this->empleado)->create(['tipo' => TipoNovedad::HoraExtraDiurna, 'fecha_inicio' => '2026-02-10', 'cantidad' => 1]);
    Novedad::factory()->for($this->empleado)->create(['tipo' => TipoNovedad::HoraExtraNocturna, 'fecha_inicio' => '2026-02-10', 'cantidad' => 1]);

    expect(($this->motivo)(1))->toBe('Supera el máximo de 2 horas extra por día; al empleado le quedan 0 horas ese día.');
});

// Issue #158, criterio 2
test('exactamente 12 horas en la semana caben y la hora 13 se rechaza', function () {
    foreach (['2026-02-09', '2026-02-10', '2026-02-11', '2026-02-12', '2026-02-13'] as $fecha) {
        Novedad::factory()->for($this->empleado)->create(['fecha_inicio' => $fecha, 'cantidad' => 2]);
    }

    expect(($this->motivo)(2, '2026-02-14'))->toBeNull();

    Novedad::factory()->for($this->empleado)->create(['fecha_inicio' => '2026-02-14', 'cantidad' => 2]);

    expect(($this->motivo)(1, '2026-02-15'))
        ->toBe('Supera el máximo de 12 horas extra por semana (de lunes a domingo); al empleado le quedan 0 horas esa semana.');
});

// Issue #158, criterio 2
test('la semana va de lunes a domingo', function () {
    foreach (['2026-02-09', '2026-02-10', '2026-02-11', '2026-02-12', '2026-02-13', '2026-02-14'] as $fecha) {
        Novedad::factory()->for($this->empleado)->create(['fecha_inicio' => $fecha, 'cantidad' => 2]);
    }

    expect(($this->motivo)(1, '2026-02-15'))->not->toBeNull()
        ->and(($this->motivo)(2, '2026-02-08'))->toBeNull()
        ->and(($this->motivo)(2, '2026-02-16'))->toBeNull();
});

// Issue #158, criterio 2
test('al editar no se cuenta la propia novedad', function () {
    $propia = Novedad::factory()->for($this->empleado)->create(['fecha_inicio' => '2026-02-10', 'cantidad' => 2]);

    expect(($this->motivo)(2, '2026-02-10', $propia))->toBeNull()
        ->and(($this->motivo)(2))->not->toBeNull();
});

// Issue #158, criterio 2
test('los demás tipos de novedad no cuentan como horas extra', function () {
    Novedad::factory()->for($this->empleado)->create(['tipo' => TipoNovedad::RecargoNocturno, 'fecha_inicio' => '2026-02-10', 'cantidad' => 8]);

    expect(($this->motivo)(2))->toBeNull();
});

// Issue #158, criterio 3
test('registrar horas de otro empleado no cambia las novedades ya guardadas', function () {
    $existente = Novedad::factory()->for($this->empleado)->create(['fecha_inicio' => '2026-02-10', 'cantidad' => 2]);
    $otro = Empleado::factory()->create();

    expect($this->limite->motivoDeRechazo($otro->id, CarbonImmutable::parse('2026-02-10'), 2))->toBeNull()
        ->and($existente->fresh()->cantidad)->toBe(2)
        ->and(Novedad::count())->toBe(1);
});
