<?php

use App\Enums\Rol;
use App\Models\Curso;
use App\Models\Sede;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gerente = User::factory()->conRol(Rol::Administrador)->create();
    $this->villavicencio = Sede::factory()->create(['nombre' => 'Sede Villavicencio']);
    $this->bogota = Sede::factory()->create(['nombre' => 'Sede Bogotá']);
});

// Issue #72, criterio 1
test('muestra los cursos de todas las sedes en el día elegido', function () {
    Curso::factory()->for($this->villavicencio)->create(['fecha' => '2026-10-15', 'valor_curso' => 300000]);
    Curso::factory()->for($this->bogota)->create(['fecha' => '2026-10-15', 'valor_curso' => 200000]);

    $this->actingAs($this->gerente)
        ->get(route('pos.cursos.index', ['fecha' => '2026-10-15']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Cursos/Consolidado')
            ->where('fecha', '2026-10-15')
            ->has('cursos', 2)
            ->where('cursos.0.sede.nombre', 'Sede Villavicencio')
            ->where('cursos.1.sede.nombre', 'Sede Bogotá')
            ->where('valorTotal', 500000));
});

// Issue #72, criterio 1
test('solo muestra los cursos del día filtrado', function () {
    Curso::factory()->for($this->villavicencio)->create(['fecha' => '2026-10-15']);
    Curso::factory()->for($this->bogota)->create(['fecha' => '2026-10-16']);

    $this->actingAs($this->gerente)
        ->get(route('pos.cursos.index', ['fecha' => '2026-10-16']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('cursos', 1)
            ->where('cursos.0.sede.nombre', 'Sede Bogotá'));
});

// Issue #72, criterio 2
test('sin fecha muestra los cursos de hoy', function () {
    Curso::factory()->for($this->villavicencio)->create(['fecha' => today()]);
    Curso::factory()->for($this->villavicencio)->create(['fecha' => today()->subDay()]);

    $this->actingAs($this->gerente)
        ->get(route('pos.cursos.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('fecha', today()->toDateString())
            ->has('cursos', 1));
});

// Issue #72, criterio 1
test('un día sin cursos muestra la lista vacía y total cero', function () {
    $this->actingAs($this->gerente)
        ->get(route('pos.cursos.index', ['fecha' => '2026-01-01']))
        ->assertInertia(fn (Assert $page) => $page->has('cursos', 0)->where('valorTotal', 0));
});

// Issue #72, criterio 3
test('la fecha debe ser válida', function () {
    $this->actingAs($this->gerente)
        ->get(route('pos.cursos.index', ['fecha' => 'ayer']))
        ->assertSessionHasErrors(['fecha' => 'Escriba una fecha válida.']);
});

// Issue #72, criterio 4
test('el usuario de una sede no puede ver los cursos de todas las sedes', function () {
    $usuarioSede = User::factory()->paraSede($this->villavicencio)->create();

    $this->actingAs($usuarioSede)->get(route('pos.cursos.index'))->assertForbidden();
});
