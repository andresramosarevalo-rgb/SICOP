<?php

use App\Enums\Rol;
use App\Models\CierreCaja;
use App\Models\Consignacion;
use App\Models\Curso;
use App\Models\Empleado;
use App\Models\Novedad;
use App\Models\PeriodoNomina;
use App\Models\Sede;
use App\Models\Soat;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->sede = Sede::factory()->create();
    $this->otraSede = Sede::factory()->create();
});

test('el cajero va a su punto de venta en lugar del dashboard', function () {
    $this->actingAs(User::factory()->paraSede($this->sede)->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('pos.inicio.index'));
});

test('el super administrador ve los indicadores de POS y de nómina de todas las sedes', function () {
    Curso::factory()->for($this->sede)->create(['fecha' => today(), 'valor_curso' => 100000]);
    Curso::factory()->for($this->otraSede)->create(['fecha' => today(), 'valor_curso' => 50000]);
    Soat::factory()->for($this->sede)->create(['fecha' => today(), 'valor_soat' => 200000]);
    Empleado::factory()->create(['sede_id' => $this->sede->id]);
    Empleado::factory()->create(['sede_id' => $this->otraSede->id, 'es_activo' => false]);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('pos.cursos.cantidad', 2)
            ->where('pos.cursos.valor', '150000.00')
            ->where('pos.soats.cantidad', 1)
            ->where('pos.soats.valor', '200000.00')
            ->where('nomina.empleados_activos', 1));
});

test('el administrador de una sede solo ve los indicadores de su sede', function () {
    Curso::factory()->for($this->sede)->create(['fecha' => today(), 'valor_curso' => 100000]);
    Curso::factory()->for($this->otraSede)->create(['fecha' => today(), 'valor_curso' => 50000]);
    Empleado::factory()->create(['sede_id' => $this->sede->id]);
    Empleado::factory()->create(['sede_id' => $this->otraSede->id]);
    PeriodoNomina::factory()->create(['sede_id' => $this->otraSede->id]);

    $this->actingAs(User::factory()->enSede($this->sede)->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('pos.cursos.cantidad', 1)
            ->where('pos.cursos.valor', '100000.00')
            ->where('nomina.empleados_activos', 1)
            ->where('nomina.periodos_abiertos', 0));
});

test('las ventas de otros meses no cuentan en el mes pero sí en la tendencia', function () {
    Curso::factory()->for($this->sede)->create(['fecha' => today()->subMonthNoOverflow(), 'valor_curso' => 80000]);
    Curso::factory()->for($this->sede)->create(['fecha' => today(), 'valor_curso' => 20000]);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('pos.cursos.cantidad', 1)
            ->has('pos.tendencia', 6)
            ->where('pos.tendencia.5.cursos', '20000.00')
            ->where('pos.tendencia.4.cursos', '80000.00')
            ->where('pos.tendencia.0.cursos', '0.00'));
});

test('el saldo por consignar descuenta lo consignado y cuenta los días sin consignar', function () {
    $cierre = CierreCaja::factory()->cerrado()->for($this->sede)->create(['fecha' => today()->subDays(2), 'valor_a_consignar' => 300000]);
    Consignacion::factory()->create(['cierre_caja_id' => $cierre->id, 'valor_consignado' => 250000]);
    CierreCaja::factory()->cerrado()->for($this->sede)->create(['fecha' => today()->subDay(), 'valor_a_consignar' => 100000]);
    CierreCaja::factory()->for($this->sede)->create(['fecha' => today(), 'valor_a_consignar' => null]);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('pos.saldo_por_consignar', '150000.00')
            ->where('pos.dias_sin_consignar', 1));
});

test('los periodos cerrados y las novedades liquidadas no cuentan como pendientes', function () {
    PeriodoNomina::factory()->create(['sede_id' => $this->sede->id]);
    $cerrado = PeriodoNomina::factory()->cerrado()->create(['sede_id' => $this->sede->id, 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-01-31']);
    Novedad::factory()->create(['empleado_id' => Empleado::factory()->create(['sede_id' => $this->sede->id])]);
    Novedad::factory()->liquidada($cerrado)->create(['empleado_id' => Empleado::factory()->create(['sede_id' => $this->sede->id])]);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nomina.periodos_abiertos', 1)
            ->where('nomina.novedades_sin_liquidar', 1));
});

test('cada rol solo recibe los indicadores de los módulos que puede gestionar', function () {
    $this->actingAs(User::factory()->enSede($this->sede, Rol::AuxiliarNomina)->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('pos', null)->has('nomina'));
});

test('la recarga parcial del dashboard refleja un curso registrado después de abrirlo', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $primera = $this->get(route('dashboard'));
    $primera->assertInertia(fn (Assert $page) => $page->where('pos.cursos.cantidad', 0));

    Curso::factory()->for($this->sede)->create(['fecha' => today(), 'valor_curso' => 90000]);

    $this->get(route('dashboard'), ['X-Inertia' => 'true', 'X-Inertia-Version' => $primera->viewData('page')['version'], 'X-Inertia-Partial-Component' => 'Dashboard', 'X-Inertia-Partial-Data' => 'pos,nomina'])
        ->assertJsonPath('props.pos.cursos.cantidad', 1)
        ->assertJsonPath('props.pos.cursos.valor', '90000.00');
});
