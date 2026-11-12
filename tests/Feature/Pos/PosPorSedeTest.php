<?php

use App\Models\Auditoria;
use App\Models\CierreCaja;
use App\Models\Consignacion;
use App\Models\Curso;
use App\Models\Sede;
use App\Models\Soat;
use App\Models\TarifaGestor;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->sedeA = Sede::factory()->create(['nombre' => 'Sede A']);
    $this->sedeB = Sede::factory()->create(['nombre' => 'Sede B']);
    $this->administradorA = User::factory()->enSede($this->sedeA)->create();
    $this->superAdmin = User::factory()->superAdmin()->create();
});

// Issue #174, criterio 1: cursos, SOAT, cierres y consignaciones de la sede.
test('el administrador de sede solo ve los cursos y SOAT de su sede', function () {
    Curso::factory()->for($this->sedeA)->create(['fecha' => '2026-10-15']);
    Curso::factory()->for($this->sedeB)->create(['fecha' => '2026-10-15']);
    Soat::factory()->for($this->sedeA)->create(['fecha' => '2026-10-15']);
    Soat::factory()->for($this->sedeB)->create(['fecha' => '2026-10-15']);

    $this->actingAs($this->administradorA)
        ->get(route('pos.cursos.index', ['fecha' => '2026-10-15']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('cursos', 1)
            ->where('cursos.0.sede.nombre', 'Sede A'));

    $this->get(route('pos.soats.index', ['fecha' => '2026-10-15']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('soats', 1)
            ->where('soats.0.sede.nombre', 'Sede A'));

    $csv = $this->get(route('pos.cursos.exportar', ['fecha' => '2026-10-15']))->streamedContent();
    expect($csv)->toContain('Sede A')->not->toContain('Sede B');

    $csv = $this->get(route('pos.soats.exportar', ['fecha' => '2026-10-15']))->streamedContent();
    expect($csv)->toContain('Sede A')->not->toContain('Sede B');
});

test('el administrador de sede solo ve el cierre y las consignaciones de su sede', function () {
    CierreCaja::factory()->cerrado()->for($this->sedeA)->create(['fecha' => '2026-10-15', 'valor_a_consignar' => 100000]);
    CierreCaja::factory()->cerrado()->for($this->sedeB)->create(['fecha' => '2026-10-15', 'valor_a_consignar' => 200000]);

    $this->actingAs($this->administradorA)
        ->get(route('pos.cierres.index', ['fecha' => '2026-10-15']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('filas', 1)
            ->where('filas.0.sede.nombre', 'Sede A'));

    $csv = $this->get(route('pos.cierres.exportar', ['fecha' => '2026-10-15']))->streamedContent();
    expect($csv)->toContain('Sede A')->not->toContain('Sede B');

    $this->get(route('pos.consignaciones.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('filas', 1)
            ->where('filas.0.sede.nombre', 'Sede A'));

    $this->get(route('pos.consignaciones.index', ['sede_id' => $this->sedeB->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sedeId', null)
            ->where('dias', []));
});

// Issue #174, criterio 2: no opera registros de otra sede.
test('el administrador de sede no opera registros de otra sede', function () {
    $curso = Curso::factory()->for($this->sedeB)->create();
    $cierre = CierreCaja::factory()->cerrado()->for($this->sedeB)->create(['valor_a_consignar' => 50000]);
    $consignacion = Consignacion::factory()->for($cierre, 'cierreCaja')->create();

    $this->actingAs($this->administradorA)
        ->get(route('pos.sedes.cursos.index', $this->sedeB))->assertForbidden();
    $this->get(route('pos.cursos.edit', $curso))->assertForbidden();
    $this->get(route('pos.sedes.cierre.show', $this->sedeB))->assertForbidden();
    $this->post(route('pos.sedes.cierre.reabrir', $this->sedeB), ['fecha' => $cierre->fecha->toDateString()])->assertForbidden();
    $this->delete(route('pos.consignaciones.destroy', $consignacion))->assertForbidden();
    $this->post(route('pos.cierre.consignacion.store', CierreCaja::factory()->cerrado()->for($this->sedeB)->create(['fecha' => today()->subDay(), 'valor_a_consignar' => 10000])))->assertForbidden();

    $this->assertDatabaseHas('consignaciones', ['id' => $consignacion->id]);
});

// Issue #174, criterio 3: tarifas del gestor de su sede.
test('el administrador de sede solo ve y ajusta la tarifa de su sede', function () {
    $this->actingAs($this->administradorA)
        ->get(route('pos.tarifas.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sedeId', $this->sedeA->id)
            ->where('puedeEditarBase', false)
            ->has('sedes', 1));

    $this->get(route('pos.tarifas.index', ['sede_id' => $this->sedeB->id]))
        ->assertInertia(fn (Assert $page) => $page->where('sedeId', $this->sedeA->id));

    $datos = ['codigo_infraccion' => 'A', 'valor_pago_gestor' => 12000];

    $this->post(route('pos.tarifas.store'), [...$datos, 'sede_id' => $this->sedeA->id])->assertRedirect();
    $this->post(route('pos.tarifas.store'), [...$datos, 'sede_id' => $this->sedeB->id])->assertForbidden();
    $this->post(route('pos.tarifas.store'), $datos)->assertForbidden();

    $ajusteB = TarifaGestor::factory()->create(['sede_id' => $this->sedeB->id]);
    $this->delete(route('pos.tarifas.destroy', $ajusteB))->assertForbidden();

    $this->assertDatabaseHas('tarifas_gestor', ['sede_id' => $this->sedeA->id, 'codigo_infraccion' => 'A']);
    $this->assertDatabaseMissing('tarifas_gestor', ['sede_id' => $this->sedeB->id, 'valor_pago_gestor' => 12000]);
});

// Issue #174, criterio 4: auditoría de su sede.
test('el administrador de sede solo ve la auditoría de su sede', function () {
    Auditoria::factory()->create(['sede_id' => $this->sedeA->id, 'descripcion' => 'Evento de A']);
    Auditoria::factory()->create(['sede_id' => $this->sedeB->id, 'descripcion' => 'Evento de B']);
    Auditoria::factory()->create(['sede_id' => null, 'descripcion' => 'Evento global']);

    $respuesta = $this->actingAs($this->administradorA)->get(route('admin.auditoria.index'));

    $descripciones = collect($respuesta->viewData('page')['props']['auditorias']['data'])->pluck('descripcion');

    expect($descripciones)->toContain('Evento de A')
        ->not->toContain('Evento de B')
        ->not->toContain('Evento global');

    $respuesta->assertInertia(fn (Assert $page) => $page->has('sedes', 1)->has('usuarios', 1)->etc());
});

// Issue #174, criterio 5: el super administrador ve todas las sedes.
test('el super administrador ve todas las sedes en POS y auditoría', function () {
    Curso::factory()->for($this->sedeA)->create(['fecha' => '2026-10-15']);
    Curso::factory()->for($this->sedeB)->create(['fecha' => '2026-10-15']);
    Auditoria::factory()->create(['sede_id' => $this->sedeA->id]);
    Auditoria::factory()->create(['sede_id' => $this->sedeB->id]);

    $this->actingAs($this->superAdmin)
        ->get(route('pos.cursos.index', ['fecha' => '2026-10-15']))
        ->assertInertia(fn (Assert $page) => $page->has('cursos', 2));

    $this->get(route('pos.tarifas.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sedeId', null)
            ->where('puedeEditarBase', true)
            ->has('sedes', 2));

    $descripciones = collect($this->get(route('admin.auditoria.index'))->viewData('page')['props']['auditorias']['data'])
        ->pluck('sede_id');

    expect($descripciones)->toContain($this->sedeA->id)->toContain($this->sedeB->id);

    $this->get(route('pos.sedes.create'))->assertOk();
});
