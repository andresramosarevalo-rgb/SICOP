<?php

use App\Actions\Nomina\LiquidarPeriodoNomina;
use App\Enums\Rol;
use App\Models\Area;
use App\Models\Contrato;
use App\Models\Empleado;
use App\Models\Novedad;
use App\Models\PeriodoNomina;
use App\Models\ReciboNomina;
use App\Models\Sede;
use App\Models\User;
use Database\Seeders\NominaSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(NominaSeeder::class);
    $this->sedeA = Sede::factory()->create(['nombre' => 'Sede A']);
    $this->sedeB = Sede::factory()->create(['nombre' => 'Sede B']);
    $this->auxiliarA = User::factory()->enSede($this->sedeA, Rol::AuxiliarNomina)->create();
    $this->superAdmin = User::factory()->superAdmin()->create();
    $this->empleadoA = Empleado::factory()->create(['sede_id' => $this->sedeA->id, 'nombres' => 'Ana']);
    $this->empleadoB = Empleado::factory()->create(['sede_id' => $this->sedeB->id, 'nombres' => 'Beto']);
});

// Issue #175, criterio 1: el empleado queda en la sede de quien lo registra.
test('el empleado registrado por una sede queda en esa sede', function () {
    $this->actingAs($this->auxiliarA)
        ->post(route('nomina.empleados.store'), datosEmpleado(['sede_id' => $this->sedeB->id, 'area_id' => Area::factory()->create()->id]))
        ->assertRedirect();

    expect(Empleado::where('numero_documento', '1012345678')->sole()->sede_id)->toBe($this->sedeA->id);
});

test('el super administrador elige la sede del empleado y es obligatoria', function () {
    $this->actingAs($this->superAdmin)
        ->post(route('nomina.empleados.store'), [...datosEmpleado(['area_id' => Area::factory()->create()->id]), 'sede_id' => null])
        ->assertSessionHasErrors('sede_id');

    $this->post(route('nomina.empleados.store'), datosEmpleado(['sede_id' => $this->sedeB->id, 'area_id' => Area::factory()->create()->id]))->assertRedirect();

    expect(Empleado::where('numero_documento', '1012345678')->sole()->sede_id)->toBe($this->sedeB->id);
});

// Issue #175, criterio 2: ve solo lo de su sede.
test('el auxiliar de sede solo ve empleados, novedades y selectores de su sede', function () {
    Novedad::factory()->create(['empleado_id' => $this->empleadoA->id]);
    Novedad::factory()->create(['empleado_id' => $this->empleadoB->id]);

    $this->actingAs($this->auxiliarA)
        ->get(route('nomina.empleados.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('empleados', 1)
            ->where('empleados.0.nombres', 'Ana'));

    $this->get(route('nomina.novedades.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('novedades', 1)
            ->where('novedades.0.empleado_id', $this->empleadoA->id)
            ->has('empleados', 1));

    $this->get(route('nomina.novedades.create'))
        ->assertInertia(fn (Assert $page) => $page->has('empleados', 1)->where('empleados.0.id', $this->empleadoA->id));
});

test('el auxiliar de sede no abre ni modifica registros de otra sede', function () {
    $novedadB = Novedad::factory()->create(['empleado_id' => $this->empleadoB->id]);
    $contratoB = Contrato::factory()->create(['empleado_id' => $this->empleadoB->id]);
    $reciboB = ReciboNomina::factory()->create(['empleado_id' => $this->empleadoB->id, 'contrato_id' => $contratoB->id]);

    $this->actingAs($this->auxiliarA)
        ->get(route('nomina.empleados.show', $this->empleadoB))->assertNotFound();
    $this->get(route('nomina.empleados.edit', $this->empleadoB))->assertNotFound();
    $this->get(route('nomina.empleados.contratos.create', $this->empleadoB))->assertNotFound();
    $this->get(route('nomina.novedades.edit', $novedadB))->assertNotFound();
    $this->delete(route('nomina.novedades.destroy', $novedadB))->assertNotFound();
    $this->get(route('nomina.recibos.show', $reciboB))->assertNotFound();

    $this->post(route('nomina.novedades.store'), [
        'empleado_id' => $this->empleadoB->id,
        'tipo' => 'ausencia',
        'fecha_inicio' => '2026-02-10',
        'fecha_fin' => '2026-02-11',
    ])->assertSessionHasErrors('empleado_id');

    $this->assertDatabaseHas('novedades', ['id' => $novedadB->id]);
});

// Issue #175, criterio 3: el período de una sede liquida solo a sus empleados.
test('el auxiliar de sede crea y liquida solo periodos de su sede con sus empleados', function () {
    Contrato::factory()->create(['empleado_id' => $this->empleadoA->id]);
    Contrato::factory()->create(['empleado_id' => $this->empleadoB->id]);

    $this->actingAs($this->auxiliarA)->post(route('nomina.periodos.store'), [
        'sede_id' => $this->sedeB->id,
        'periodicidad_pago' => 'mensual',
        'fecha_inicio' => '2026-02-01',
        'fecha_fin' => '2026-02-28',
    ])->assertRedirect();

    $periodo = PeriodoNomina::sole();

    expect($periodo->sede_id)->toBe($this->sedeA->id);

    $this->post(route('nomina.periodos.liquidar', $periodo))->assertRedirect();

    expect(ReciboNomina::pluck('empleado_id')->all())->toBe([$this->empleadoA->id]);

    $periodoB = PeriodoNomina::factory()->create(['sede_id' => $this->sedeB->id, 'fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-31']);

    $this->post(route('nomina.periodos.liquidar', $periodoB))->assertNotFound();
    $this->get(route('nomina.periodos.index'))
        ->assertInertia(fn (Assert $page) => $page->has('periodos', 1)->where('periodos.0.sede_id', $this->sedeA->id));
});

// Issue #175, criterio 4: el super administrador liquida una sede o todas.
test('el super administrador liquida una sede o todas las sedes', function () {
    Contrato::factory()->create(['empleado_id' => $this->empleadoA->id]);
    Contrato::factory()->create(['empleado_id' => $this->empleadoB->id]);
    $sinSede = Empleado::factory()->create(['sede_id' => null]);
    Contrato::factory()->create(['empleado_id' => $sinSede->id]);

    $deSede = PeriodoNomina::factory()->create(['sede_id' => $this->sedeB->id]);
    $deTodas = PeriodoNomina::factory()->create(['sede_id' => null, 'fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-31']);

    app(LiquidarPeriodoNomina::class)->ejecutar($deSede, $this->superAdmin);
    app(LiquidarPeriodoNomina::class)->ejecutar($deTodas, $this->superAdmin);

    expect($deSede->recibos()->pluck('empleado_id')->all())->toBe([$this->empleadoB->id])
        ->and($deTodas->recibos()->count())->toBe(3);
});

test('el periodo de todas las sedes lo crea solo el super administrador y no se cruza con el de una sede', function () {
    $datos = ['periodicidad_pago' => 'mensual', 'fecha_inicio' => '2026-02-01', 'fecha_fin' => '2026-02-28'];

    $this->actingAs($this->superAdmin)->post(route('nomina.periodos.store'), $datos)->assertRedirect();
    expect(PeriodoNomina::sole()->sede_id)->toBeNull();

    $this->post(route('nomina.periodos.store'), [...$datos, 'sede_id' => $this->sedeA->id])
        ->assertSessionHasErrors('fecha_inicio');

    $this->actingAs($this->auxiliarA)->post(route('nomina.periodos.store'), $datos)
        ->assertSessionHasErrors('fecha_inicio');

    expect(PeriodoNomina::count())->toBe(1);
});

// Issue #175, criterio 5: los empleados sin sede solo los ve el super administrador.
test('los empleados sin sede solo los ve el super administrador', function () {
    $sinSede = Empleado::factory()->create(['sede_id' => null, 'nombres' => 'Sin sede']);
    $usuarioSinSede = User::factory()->conRol(Rol::AuxiliarNomina)->create();

    $this->actingAs($usuarioSinSede)
        ->get(route('nomina.empleados.index'))
        ->assertInertia(fn (Assert $page) => $page->has('empleados', 0));

    $this->actingAs($this->auxiliarA)->get(route('nomina.empleados.show', $sinSede))->assertNotFound();

    $this->actingAs($this->superAdmin)
        ->get(route('nomina.empleados.index'))
        ->assertInertia(fn (Assert $page) => $page->has('empleados', 3));

    $this->get(route('nomina.empleados.show', $sinSede))->assertOk();
});
