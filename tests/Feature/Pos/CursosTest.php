<?php

use App\Enums\Rol;
use App\Models\Curso;
use App\Models\Sede;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gerente = User::factory()->conRol(Rol::Administrador)->create();
    $this->sede = Sede::factory()->create();
});

function datosCurso(array $sobrescribir = []): array
{
    return [
        'fecha' => today()->toDateString(),
        'tipo_documento' => 'CC',
        'numero_documento' => '1006123456',
        'nombre_cliente' => 'Carlos Pérez',
        'tipo_comparendo' => 'Tipo C',
        'valor_curso' => '350000',
        'porcentaje_descuento' => 50,
        ...$sobrescribir,
    ];
}

// Issue #69, criterio 1
test('el listado muestra los cursos de la sede, los más recientes primero', function () {
    Curso::factory()->for($this->sede)->create(['fecha' => '2026-10-14', 'nombre_cliente' => 'Antiguo', 'valor_curso' => 350000, 'porcentaje_descuento' => 50]);
    Curso::factory()->for($this->sede)->create(['fecha' => '2026-10-15', 'nombre_cliente' => 'Reciente']);

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.cursos.index', $this->sede))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Cursos/Index')
            ->where('sede.id', $this->sede->id)
            ->has('cursos', 2)
            ->where('cursos.0.nombre_cliente', 'Reciente')
            ->where('cursos.1.nombre_cliente', 'Antiguo')
            ->where('cursos.1.valor_curso', '350000.00')
            ->where('cursos.1.porcentaje_descuento', '50.00'));
});

// Issue #69, criterio 5
test('el listado de una sede no muestra los cursos de otra', function () {
    Curso::factory()->create();
    Curso::factory()->for($this->sede)->create();

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.cursos.index', $this->sede))
        ->assertInertia(fn (Assert $page) => $page->has('cursos', 1));
});

// Issue #67, criterio 2
test('el cajero recibe 403 en los cursos', function () {
    $cajero = User::factory()->conRol(Rol::Cajero)->create();

    $this->actingAs($cajero)->get(route('pos.sedes.cursos.index', $this->sede))->assertForbidden();
});
