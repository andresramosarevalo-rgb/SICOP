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

// Issue #12, criterio 2
test('una incapacidad registrada abarca los días de su rango', function () {
    $this->actingAs($this->auxiliar)
        ->post(route('nomina.novedades.store'), [
            'empleado_id' => $this->empleado->id,
            'tipo' => TipoNovedad::Incapacidad->value,
            'fecha_inicio' => '2026-02-10',
            'fecha_fin' => '2026-02-12',
        ])
        ->assertSessionHasNoErrors();

    expect(Novedad::sole()->dias())->toBe(3);
});

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

// Issue #12, criterio 4
test('una novedad ya liquidada no se puede editar ni eliminar', function () {
    $novedad = Novedad::factory()->for($this->empleado)->liquidada()->create();

    $this->actingAs($this->auxiliar)
        ->put(route('nomina.novedades.update', $novedad), [
            'empleado_id' => $this->empleado->id,
            'tipo' => TipoNovedad::HoraExtraDiurna->value,
            'fecha_inicio' => '2026-02-10',
            'cantidad' => 8,
        ])
        ->assertForbidden();

    $this->actingAs($this->auxiliar)
        ->delete(route('nomina.novedades.destroy', $novedad))
        ->assertForbidden();

    expect($novedad->fresh()->cantidad)->toBe(2);
});

// Issue #12, criterio 4
test('una novedad sin liquidar se puede editar y eliminar', function () {
    $novedad = Novedad::factory()->for($this->empleado)->create();

    $this->actingAs($this->auxiliar)
        ->put(route('nomina.novedades.update', $novedad), [
            'empleado_id' => $this->empleado->id,
            'tipo' => TipoNovedad::HoraExtraDiurna->value,
            'fecha_inicio' => '2026-02-10',
            'cantidad' => 8,
        ])
        ->assertRedirect(route('nomina.novedades.index'));

    expect($novedad->fresh()->cantidad)->toBe(8);

    $this->actingAs($this->auxiliar)
        ->delete(route('nomina.novedades.destroy', $novedad))
        ->assertRedirect(route('nomina.novedades.index'));

    expect($novedad->fresh())->toBeNull();
});
