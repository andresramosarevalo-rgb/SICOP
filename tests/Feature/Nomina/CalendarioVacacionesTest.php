<?php

use App\Actions\Nomina\CalendarioVacaciones;
use App\Actions\Nomina\FestivosColombia;
use App\Enums\TipoNovedad;
use App\Models\Contrato;
use App\Models\Empleado;
use App\Models\Novedad;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

// Issue #188, criterio 2: calendario nacional completo, incluidos móviles y nuevo festivo.
test('obtiene los festivos nacionales de 2026', function () {
    expect((new FestivosColombia)->fechas(2026))->toBe([
        '2026-01-01', '2026-01-12', '2026-03-23', '2026-04-02', '2026-04-03',
        '2026-05-01', '2026-05-18', '2026-06-08', '2026-06-15', '2026-06-29',
        '2026-07-13', '2026-07-20', '2026-08-07', '2026-08-17',
        '2026-10-12', '2026-11-02', '2026-11-16', '2026-12-08', '2026-12-25',
    ]);
});

// Issue #188, criterio 2: sábado hábil, domingo excluido, traslado y Semana Santa.
test('cuenta días con el calendario empresarial confirmado', function (string $inicio, string $fin, int $esperado) {
    expect(app(CalendarioVacaciones::class)->contarDias(
        CarbonImmutable::parse($inicio), CarbonImmutable::parse($fin),
    ))->toBe($esperado);
})->with([
    'sábado' => ['2026-11-07', '2026-11-07', 1],
    'domingo' => ['2026-11-08', '2026-11-08', 0],
    'todos los santos trasladado' => ['2026-11-02', '2026-11-06', 4],
    'viernes a lunes' => ['2026-11-06', '2026-11-09', 3],
    'semana santa' => ['2026-03-30', '2026-04-05', 4],
    'semana santa bisiesto y San José' => ['2024-03-25', '2024-03-31', 3],
    'festivo fijo en sábado' => ['2027-05-01', '2027-05-01', 0],
    'celebración trasladada sigue hábil' => ['2026-07-09', '2026-07-09', 1],
    'nuevo festivo desde 2026' => ['2026-07-13', '2026-07-13', 0],
    'sin aplicación retroactiva' => ['2025-07-14', '2025-07-14', 1],
    'cruce de año' => ['2026-12-31', '2027-01-02', 2],
    'festivo original en lunes' => ['2026-10-12', '2026-10-12', 0],
    'lunes siguiente no se excluye otra vez' => ['2026-10-19', '2026-10-19', 1],
]);

// Issue #188, criterio 2: festivos coincidentes y excepciones adicionales no duplican descuentos.
test('deduplica festivos y permite excluir fechas adicionales', function () {
    $festivos = (new FestivosColombia)->fechas(2025);
    expect(array_count_values($festivos)['2025-06-30'])->toBe(1);

    config(['vacaciones.festivos' => ['2026-11-02', '2026-11-07']]);
    expect(app(CalendarioVacaciones::class)->contarDias(
        CarbonImmutable::parse('2026-11-02'), CarbonImmutable::parse('2026-11-08'),
    ))->toBe(4);
});

// Issue #188, criterios 2, 3 y 4: integración real con la configuración de producción.
test('el expediente excluye festivos colombianos y ya no advierte calendario sin confirmar', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-15 12:00:00', 'America/Bogota'));
    $empleado = Empleado::factory()->create();
    Contrato::factory()->for($empleado)->create(['fecha_inicio' => '2025-11-16']);
    Novedad::factory()->for($empleado)->create([
        'tipo' => TipoNovedad::Vacaciones,
        'fecha_inicio' => '2026-11-02', 'fecha_fin' => '2026-11-07',
    ]);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('nomina.empleados.show', $empleado))
        ->assertInertia(fn (Assert $page) => $page
            ->where('saldoVacaciones.diasCausados', '15.0000')
            ->where('saldoVacaciones.diasUtilizados', 5)
            ->where('saldoVacaciones.diasDisponibles', '10.0000')
            ->has('saldoVacaciones.advertencias', 1)
            ->where('saldoVacaciones.criterioCalendario', fn (string $criterio) => str_contains($criterio, 'sábado') && str_contains($criterio, 'Colombia')));
});
