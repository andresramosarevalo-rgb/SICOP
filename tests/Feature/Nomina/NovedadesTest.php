<?php

use App\Enums\Rol;
use App\Enums\TipoNovedad;
use App\Models\ConceptoNomina;
use App\Models\Empleado;
use App\Models\Novedad;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->auxiliar = User::factory()->conRol(Rol::AuxiliarNomina)->create();
    $this->empleado = Empleado::factory()->create();
});

// Issue #12, criterio 1
test('una novedad de horas extra o recargo sin cantidad de horas se rechaza', function (TipoNovedad $tipo) {
    $this->actingAs($this->auxiliar)
        ->post(route('nomina.novedades.store'), [
            'empleado_id' => $this->empleado->id,
            'tipo' => $tipo->value,
            'fecha_inicio' => '2026-02-10',
        ])
        ->assertSessionHasErrors(['cantidad' => 'Indique la cantidad de horas o minutos.']);

    expect(Novedad::count())->toBe(0);
})->with([
    TipoNovedad::HoraExtraDiurna,
    TipoNovedad::HoraExtraNocturna,
    TipoNovedad::RecargoNocturno,
    TipoNovedad::DominicalFestivo,
]);

// Issue #12, criterio 1
test('una novedad de horas extra con cantidad de horas se registra', function () {
    $this->actingAs($this->auxiliar)
        ->post(route('nomina.novedades.store'), [
            'empleado_id' => $this->empleado->id,
            'tipo' => TipoNovedad::HoraExtraDiurna->value,
            'fecha_inicio' => '2026-02-10',
            'cantidad' => 3,
        ])
        ->assertRedirect(route('nomina.novedades.create'));

    $novedad = Novedad::sole();
    expect($novedad->tipo)->toBe(TipoNovedad::HoraExtraDiurna)
        ->and($novedad->cantidad)->toBe(3)
        ->and($novedad->empleado_id)->toBe($this->empleado->id);
});

// Issue #12, criterio 2
test('una incapacidad o vacaciones con fecha de fin anterior a la de inicio se rechaza', function (TipoNovedad $tipo) {
    $this->actingAs($this->auxiliar)
        ->post(route('nomina.novedades.store'), [
            'empleado_id' => $this->empleado->id,
            'tipo' => $tipo->value,
            'fecha_inicio' => '2026-02-10',
            'fecha_fin' => '2026-02-09',
        ])
        ->assertSessionHasErrors(['fecha_fin' => 'La fecha de fin no puede ser anterior a la de inicio.']);

    expect(Novedad::count())->toBe(0);
})->with([TipoNovedad::Incapacidad, TipoNovedad::Vacaciones]);

test('un concepto eventual exige el concepto y el valor', function () {
    $this->actingAs($this->auxiliar)
        ->post(route('nomina.novedades.store'), [
            'empleado_id' => $this->empleado->id,
            'tipo' => TipoNovedad::ConceptoEventual->value,
            'fecha_inicio' => '2026-02-10',
        ])
        ->assertSessionHasErrors(['concepto_nomina_id', 'valor_eventual']);

    $this->actingAs($this->auxiliar)
        ->post(route('nomina.novedades.store'), [
            'empleado_id' => $this->empleado->id,
            'tipo' => TipoNovedad::ConceptoEventual->value,
            'fecha_inicio' => '2026-02-10',
            'concepto_nomina_id' => ConceptoNomina::factory()->create()->id,
            'valor_eventual' => '50000',
        ])
        ->assertSessionHasNoErrors();
});

// Issue #12, criterio 3
test('el filtro por empleado y rango de fechas solo muestra las novedades que coinciden', function () {
    $otro = Empleado::factory()->create();
    $buscada = Novedad::factory()->for($this->empleado)->create(['fecha_inicio' => '2026-02-10']);
    Novedad::factory()->for($this->empleado)->create(['fecha_inicio' => '2026-03-10']);
    Novedad::factory()->for($otro)->create(['fecha_inicio' => '2026-02-10']);
    $incapacidad = Novedad::factory()->for($this->empleado)->create([
        'tipo' => TipoNovedad::Incapacidad, 'cantidad' => null,
        'fecha_inicio' => '2026-01-28', 'fecha_fin' => '2026-02-03',
    ]);

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.novedades.index', [
            'empleado_id' => $this->empleado->id, 'desde' => '2026-02-01', 'hasta' => '2026-02-28',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Nomina/Novedades/Index')
            ->has('novedades', 2)
            ->where('novedades.0.id', $buscada->id)
            ->where('novedades.1.id', $incapacidad->id));
});
