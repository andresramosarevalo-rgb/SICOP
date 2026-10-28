<?php

use App\Enums\Rol;
use App\Enums\TipoNovedad;
use App\Models\Empleado;
use App\Models\Novedad;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->auxiliar = User::factory()->conRol(Rol::AuxiliarNomina)->create();
    $this->empleado = Empleado::factory()->create();

    // El 10 de febrero de 2026 es martes: su semana va del 9 al 15 de febrero.
    $this->registrar = fn (TipoNovedad $tipo, string $fecha, int $horas) => $this->actingAs($this->auxiliar)
        ->post(route('nomina.novedades.store'), [
            'empleado_id' => $this->empleado->id,
            'tipo' => $tipo->value,
            'fecha_inicio' => $fecha,
            'cantidad' => $horas,
        ]);
});

// Issue #145, criterio 1
test('una hora extra que con las del día pasa de 2 horas no se guarda y dice cuántas quedan', function () {
    Novedad::factory()->for($this->empleado)->create(['tipo' => TipoNovedad::HoraExtraDiurna, 'fecha_inicio' => '2026-02-10', 'cantidad' => 1]);

    ($this->registrar)(TipoNovedad::HoraExtraNocturna, '2026-02-10', 2)
        ->assertSessionHasErrors(['cantidad' => 'Supera el máximo de 2 horas extra por día; al empleado le queda 1 hora ese día.']);

    expect(Novedad::count())->toBe(1);
});

// Issue #145, criterio 1
test('un día sin horas disponibles informa que quedan 0 horas', function () {
    Novedad::factory()->for($this->empleado)->create(['tipo' => TipoNovedad::HoraExtraNocturna, 'fecha_inicio' => '2026-02-10', 'cantidad' => 2]);

    ($this->registrar)(TipoNovedad::HoraExtraDiurna, '2026-02-10', 1)
        ->assertSessionHasErrors(['cantidad' => 'Supera el máximo de 2 horas extra por día; al empleado le quedan 0 horas ese día.']);
});

// Issue #145, criterio 2
test('una hora extra que con las de la semana pasa de 12 horas no se guarda y da el motivo', function () {
    foreach (['2026-02-09', '2026-02-10', '2026-02-11', '2026-02-12', '2026-02-13', '2026-02-14'] as $fecha) {
        Novedad::factory()->for($this->empleado)->create(['fecha_inicio' => $fecha, 'cantidad' => 2]);
    }

    ($this->registrar)(TipoNovedad::HoraExtraDiurna, '2026-02-15', 1)
        ->assertSessionHasErrors(['cantidad' => 'Supera el máximo de 12 horas extra por semana (de lunes a domingo); al empleado le quedan 0 horas esa semana.']);

    expect(Novedad::count())->toBe(6);
});

// Issue #145, criterio 2
test('las horas del domingo anterior y del lunes siguiente no cuentan en la semana', function () {
    foreach (['2026-02-09', '2026-02-10', '2026-02-11', '2026-02-12', '2026-02-13'] as $fecha) {
        Novedad::factory()->for($this->empleado)->create(['fecha_inicio' => $fecha, 'cantidad' => 2]);
    }
    Novedad::factory()->for($this->empleado)->create(['fecha_inicio' => '2026-02-08', 'cantidad' => 2]);
    Novedad::factory()->for($this->empleado)->create(['fecha_inicio' => '2026-02-16', 'cantidad' => 2]);

    ($this->registrar)(TipoNovedad::HoraExtraDiurna, '2026-02-14', 2)->assertSessionHasNoErrors();

    expect(Novedad::count())->toBe(8);
});

// Issue #145, criterio 2
test('las horas extra de otro empleado no cuentan', function () {
    Novedad::factory()->create(['fecha_inicio' => '2026-02-10', 'cantidad' => 2]);

    ($this->registrar)(TipoNovedad::HoraExtraDiurna, '2026-02-10', 2)->assertSessionHasNoErrors();
});

// Issue #145, criterio 3
test('una hora extra dentro de los dos límites se guarda y aparece en el listado', function () {
    Novedad::factory()->for($this->empleado)->create(['fecha_inicio' => '2026-02-10', 'cantidad' => 1]);

    ($this->registrar)(TipoNovedad::HoraExtraNocturna, '2026-02-10', 1)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('nomina.novedades.create'));

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.novedades.index'))
        ->assertInertia(fn (Assert $page) => $page->has('novedades', 2));
});

// Issue #145, criterio 4
test('al editar una novedad el límite no cuenta la propia novedad', function () {
    $novedad = Novedad::factory()->for($this->empleado)->create(['fecha_inicio' => '2026-02-10', 'cantidad' => 2]);

    $this->actingAs($this->auxiliar)
        ->put(route('nomina.novedades.update', $novedad), [
            'empleado_id' => $this->empleado->id,
            'tipo' => TipoNovedad::HoraExtraDiurna->value,
            'fecha_inicio' => '2026-02-10',
            'cantidad' => 2,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('nomina.novedades.index'));
});

// Issue #145, criterio 4
test('al editar una novedad sí cuentan las demás horas extra del día', function () {
    Novedad::factory()->for($this->empleado)->create(['fecha_inicio' => '2026-02-10', 'cantidad' => 1]);
    $novedad = Novedad::factory()->for($this->empleado)->create(['fecha_inicio' => '2026-02-10', 'cantidad' => 1]);

    $this->actingAs($this->auxiliar)
        ->put(route('nomina.novedades.update', $novedad), [
            'empleado_id' => $this->empleado->id,
            'tipo' => TipoNovedad::HoraExtraDiurna->value,
            'fecha_inicio' => '2026-02-10',
            'cantidad' => 2,
        ])
        ->assertSessionHasErrors(['cantidad' => 'Supera el máximo de 2 horas extra por día; al empleado le queda 1 hora ese día.']);

    expect($novedad->fresh()->cantidad)->toBe(1);
});

// Issue #145, criterio 5
test('el recargo nocturno y el dominical o festivo no tienen este límite', function (TipoNovedad $tipo) {
    Novedad::factory()->for($this->empleado)->create(['fecha_inicio' => '2026-02-10', 'cantidad' => 2]);

    ($this->registrar)($tipo, '2026-02-10', 8)->assertSessionHasNoErrors();

    expect(Novedad::count())->toBe(2);
})->with([TipoNovedad::RecargoNocturno, TipoNovedad::DominicalFestivo]);
