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
