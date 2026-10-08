<?php

use App\Enums\Rol;
use App\Models\Area;
use App\Models\Contrato;
use App\Models\Empleado;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->auxiliar = User::factory()->conRol(Rol::AuxiliarNomina)->create();
    $this->empleado = Empleado::factory()->create(['numero_documento' => '1012345678']);
});

// Issue #7, criterio 1
test('al editar el correo y el área del empleado los cambios se ven en su expediente', function () {
    $nuevaArea = Area::factory()->create(['nombre' => 'Instrucción']);

    $this->actingAs($this->auxiliar)
        ->put(route('nomina.empleados.update', $this->empleado), datosEmpleado([
            'email' => 'nuevo@example.com',
            'area_id' => $nuevaArea->id,
        ]))
        ->assertRedirect(route('nomina.empleados.show', $this->empleado));

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.empleados.show', $this->empleado))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Nomina/Empleados/Show')
            ->where('empleado.email', 'nuevo@example.com')
            ->where('empleado.area.nombre', 'Instrucción'));
});

// Issue #7, criterio 1
test('al editar no se acepta el documento de otro empleado', function () {
    Empleado::factory()->create(['numero_documento' => '79888777']);

    $this->actingAs($this->auxiliar)
        ->put(route('nomina.empleados.update', $this->empleado), datosEmpleado([
            'numero_documento' => '79888777',
            'area_id' => $this->empleado->area_id,
        ]))
        ->assertSessionHasErrors(['numero_documento' => 'Ya existe un empleado con ese número de documento.']);
});

// Issue #7, criterio 1
test('al editar se puede conservar el área actual aunque esté inactiva', function () {
    $this->empleado->area->update(['es_activa' => false]);

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.empleados.edit', $this->empleado))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Nomina/Empleados/Edit')
            ->where('areas.0.id', $this->empleado->area_id));

    $this->actingAs($this->auxiliar)
        ->put(route('nomina.empleados.update', $this->empleado), datosEmpleado([
            'cargo' => 'Coordinadora',
            'area_id' => $this->empleado->area_id,
        ]))
        ->assertSessionHasNoErrors();

    expect($this->empleado->fresh()->cargo)->toBe('Coordinadora');
});

// Issue #7, criterio 2
test('un empleado desactivado sale del filtro de activos pero conserva su expediente', function () {
    $this->actingAs($this->auxiliar)
        ->patch(route('nomina.empleados.estado', $this->empleado), ['es_activo' => false])
        ->assertRedirect(route('nomina.empleados.show', $this->empleado));

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.empleados.index', ['estado' => 'activos']))
        ->assertInertia(fn (Assert $page) => $page->has('empleados', 0));

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.empleados.index', ['estado' => 'inactivos']))
        ->assertInertia(fn (Assert $page) => $page->has('empleados', 1));

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.empleados.show', $this->empleado))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('empleado.es_activo', false));
});

// Issue #8, criterio 2
test('el expediente muestra el historial de contratos del más reciente al más antiguo', function () {
    Contrato::factory()->for($this->empleado)->create(['fecha_inicio' => '2025-01-01', 'es_vigente' => false]);
    Contrato::factory()->for($this->empleado)->create(['fecha_inicio' => '2026-01-01']);

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.empleados.show', $this->empleado))
        ->assertInertia(fn (Assert $page) => $page
            ->has('contratos', 2)
            ->where('contratos.0.fecha_inicio', '2026-01-01')
            ->where('contratos.0.tipo_contrato', 'Término indefinido')
            ->where('contratos.1.es_vigente', false));
});
