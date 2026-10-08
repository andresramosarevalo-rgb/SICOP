<?php

use App\Enums\FormaCalculo;
use App\Enums\Rol;
use App\Models\AsignacionConcepto;
use App\Models\ConceptoNomina;
use App\Models\Empleado;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->auxiliar = User::factory()->conRol(Rol::AuxiliarNomina)->create();
    $this->empleado = Empleado::factory()->create();
    $this->bono = ConceptoNomina::factory()->create(['nombre' => 'Bono de permanencia']);
});

// Issue #11, criterio 1
test('al asignar un concepto activo con valor y fecha de inicio aparece en el expediente', function () {
    $this->actingAs($this->auxiliar)
        ->post(route('nomina.empleados.asignaciones.store', $this->empleado), [
            'concepto_nomina_id' => $this->bono->id,
            'valor_asignado' => '80000',
            'fecha_inicio' => '2026-02-01',
        ])
        ->assertRedirect(route('nomina.empleados.show', $this->empleado));

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.empleados.show', $this->empleado))
        ->assertInertia(fn (Assert $page) => $page
            ->has('asignaciones', 1)
            ->where('asignaciones.0.concepto.nombre', 'Bono de permanencia')
            ->where('asignaciones.0.valor_asignado', '80000.00')
            ->where('asignaciones.0.fecha_inicio', '2026-02-01'));
});

// Issue #11, criterio 1
test('no se pueden asignar conceptos de sistema ni conceptos inactivos', function (array $atributos) {
    $concepto = ConceptoNomina::factory()->create($atributos);

    $this->actingAs($this->auxiliar)
        ->post(route('nomina.empleados.asignaciones.store', $this->empleado), [
            'concepto_nomina_id' => $concepto->id,
            'fecha_inicio' => '2026-02-01',
        ])
        ->assertSessionHasErrors(['concepto_nomina_id' => 'Seleccione un concepto activo que no sea de sistema.']);

    expect(AsignacionConcepto::count())->toBe(0);
})->with([
    'de sistema' => [['es_sistema' => true, 'forma_calculo' => FormaCalculo::Sistema]],
    'inactivo' => [['es_activo' => false]],
]);

// Issue #11, criterio 2
test('no se puede asignar un concepto con fecha de fin anterior a la de inicio', function () {
    $this->actingAs($this->auxiliar)
        ->post(route('nomina.empleados.asignaciones.store', $this->empleado), [
            'concepto_nomina_id' => $this->bono->id,
            'fecha_inicio' => '2026-02-01',
            'fecha_fin' => '2026-01-31',
        ])
        ->assertSessionHasErrors(['fecha_fin' => 'La fecha de fin no puede ser anterior a la de inicio.']);

    expect(AsignacionConcepto::count())->toBe(0);
});

// Issue #11, criterio 3
test('al retirar un concepto asignado deja de aparecer en el expediente', function () {
    $asignacion = AsignacionConcepto::factory()->for($this->empleado)->create(['concepto_nomina_id' => $this->bono->id]);

    $this->actingAs($this->auxiliar)
        ->delete(route('nomina.asignaciones.destroy', $asignacion))
        ->assertRedirect(route('nomina.empleados.show', $this->empleado));

    $this->actingAs($this->auxiliar)
        ->get(route('nomina.empleados.show', $this->empleado))
        ->assertInertia(fn (Assert $page) => $page->has('asignaciones', 0));
});
