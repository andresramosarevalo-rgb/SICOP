<?php

use App\Enums\Rol;
use App\Models\Sede;
use App\Models\Soat;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gerente = User::factory()->conRol(Rol::Administrador)->create();
    $this->villavicencio = Sede::factory()->create(['nombre' => 'Sede Villavicencio']);
    $this->bogota = Sede::factory()->create(['nombre' => 'Sede Bogotá']);
});

// Issue #72, criterio 1
test('muestra los SOAT de todas las sedes en el día elegido', function () {
    Soat::factory()->for($this->villavicencio)->create(['fecha' => '2026-10-15', 'valor_soat' => 300000]);
    Soat::factory()->for($this->bogota)->create(['fecha' => '2026-10-15', 'valor_soat' => 200000]);

    $this->actingAs($this->gerente)
        ->get(route('pos.soats.index', ['fecha' => '2026-10-15']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Soats/Consolidado')
            ->where('fecha', '2026-10-15')
            ->has('soats', 2)
            ->where('soats.0.sede.nombre', 'Sede Villavicencio')
            ->where('soats.1.sede.nombre', 'Sede Bogotá')
            ->where('valorTotal', 500000));
});

// Issue #72, criterio 1
test('solo muestra los SOAT del día filtrado', function () {
    Soat::factory()->for($this->villavicencio)->create(['fecha' => '2026-10-15']);
    Soat::factory()->for($this->bogota)->create(['fecha' => '2026-10-16']);

    $this->actingAs($this->gerente)
        ->get(route('pos.soats.index', ['fecha' => '2026-10-16']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('soats', 1)
            ->where('soats.0.sede.nombre', 'Sede Bogotá'));
});

// Issue #72, criterio 2
test('sin fecha muestra los SOAT de hoy', function () {
    Soat::factory()->for($this->villavicencio)->create(['fecha' => today()]);
    Soat::factory()->for($this->villavicencio)->create(['fecha' => today()->subDay()]);

    $this->actingAs($this->gerente)
        ->get(route('pos.soats.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('fecha', today()->toDateString())
            ->has('soats', 1));
});

// Issue #72, criterio 1
test('un día sin soats muestra la lista vacía y total cero', function () {
    $this->actingAs($this->gerente)
        ->get(route('pos.soats.index', ['fecha' => '2026-01-01']))
        ->assertInertia(fn (Assert $page) => $page->has('soats', 0)->where('valorTotal', 0));
});

// Issue #72, criterio 3
test('la fecha debe ser válida', function () {
    $this->actingAs($this->gerente)
        ->get(route('pos.soats.index', ['fecha' => 'ayer']))
        ->assertSessionHasErrors(['fecha' => 'Escriba una fecha válida.']);
});

// Issue #72, criterio 4
test('el usuario de una sede no puede ver los SOAT de todas las sedes', function () {
    $usuarioSede = User::factory()->paraSede($this->villavicencio)->create();

    $this->actingAs($usuarioSede)->get(route('pos.soats.index'))->assertForbidden();
});
