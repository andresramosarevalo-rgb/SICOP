<?php

use App\Enums\EstadoCierre;
use App\Enums\Rol;
use App\Models\CierreCaja;
use App\Models\Curso;
use App\Models\GastoCaja;
use App\Models\Sede;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gerente = User::factory()->conRol(Rol::Administrador)->create();
    $this->sede = Sede::factory()->create(['valor_base_caja' => 100000]);
    $this->usuarioSede = User::factory()->paraSede($this->sede)->create();
});

function cerrarDiaDeEjemplo(Sede $sede, string $fecha, array $cifras = []): CierreCaja
{
    return CierreCaja::factory()->cerrado()->for($sede)->create([
        'fecha' => $fecha,
        'valor_base_caja' => 100000,
        'valor_efectivo_ventas' => 300000,
        'valor_pago_gestores' => 0,
        'valor_gastos' => 0,
        'valor_efectivo_esperado' => 400000,
        'valor_efectivo_contado' => 400000,
        'valor_diferencia_efectivo' => 0,
        'valor_ventas_datafono' => 0,
        'valor_reportado_datafono' => 0,
        'valor_diferencia_datafono' => 0,
        'valor_ventas_qr' => 0,
        'valor_reportado_qr' => 0,
        'valor_diferencia_qr' => 0,
        'valor_a_consignar' => 300000,
        ...$cifras,
    ]);
}

// Issue #78, criterio 1
test('el gerente reabre un día cerrado y se descartan las cifras del arqueo', function () {
    $cierre = cerrarDiaDeEjemplo($this->sede, today()->toDateString(), ['justificacion' => 'Faltaba un recibo', 'cerrado_por' => $this->usuarioSede->id]);

    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cierre.reabrir', $this->sede), ['fecha' => today()->toDateString()])
        ->assertRedirect(route('pos.sedes.cierre.show', ['sede' => $this->sede, 'fecha' => today()->toDateString()]));

    expect($cierre->fresh())
        ->estado->toBe(EstadoCierre::Abierto)
        ->valor_efectivo_contado->toBeNull()
        ->valor_a_consignar->toBeNull()
        ->justificacion->toBeNull()
        ->cerrado_por->toBeNull()
        ->cerrado_en->toBeNull()
        ->reabierto_por->toBe($this->gerente->id)
        ->reabierto_en->not->toBeNull();
});

// Issue #78, criterio 1
test('al reabrir se conservan los gastos y se vuelve a poder registrar ventas', function () {
    $cierre = cerrarDiaDeEjemplo($this->sede, today()->toDateString());
    GastoCaja::factory()->for($cierre)->create();

    $this->actingAs($this->gerente)->post(route('pos.sedes.cierre.reabrir', $this->sede), ['fecha' => today()->toDateString()]);

    expect($cierre->gastos()->count())->toBe(1)
        ->and(CierreCaja::diaCerrado($this->sede->id, today()))->toBeFalse();

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.cierre.show', $this->sede))
        ->assertInertia(fn (Assert $page) => $page->where('estaCerrado', false)->where('cierre', null)->has('gastos', 1));
});

// Issue #78, criterio 1
test('después de reabrir el día se puede cerrar de nuevo con las cifras corregidas', function () {
    cerrarDiaDeEjemplo($this->sede, today()->toDateString());
    Curso::factory()->for($this->sede)->create(['fecha' => today(), 'valor_efectivo' => 300000, 'valor_pago_gestor' => 0]);

    $this->actingAs($this->gerente)->post(route('pos.sedes.cierre.reabrir', $this->sede), ['fecha' => today()->toDateString()]);

    $this->actingAs($this->usuarioSede)
        ->post(route('pos.sedes.cierre.store', $this->sede), ['fecha' => today()->toDateString(), 'valor_efectivo_contado' => '400000', 'valor_reportado_datafono' => '0', 'valor_reportado_qr' => '0'])
        ->assertSessionHasNoErrors();

    expect(CierreCaja::first())
        ->estaCerrado()->toBeTrue()
        ->valor_a_consignar->toBe('300000.00');
});

// Issue #78, criterio 2
test('solo el gerente puede reabrir un día', function () {
    cerrarDiaDeEjemplo($this->sede, today()->toDateString());

    $this->actingAs($this->usuarioSede)
        ->post(route('pos.sedes.cierre.reabrir', $this->sede), ['fecha' => today()->toDateString()])
        ->assertForbidden();

    expect(CierreCaja::first()->estaCerrado())->toBeTrue();
});

// Issue #78, criterio 4
test('no se puede reabrir un día que no está cerrado ni uno sin cierre', function () {
    CierreCaja::factory()->for($this->sede)->create(['fecha' => today()]);

    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cierre.reabrir', $this->sede), ['fecha' => today()->toDateString()])
        ->assertForbidden();

    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cierre.reabrir', $this->sede), ['fecha' => today()->subDay()->toDateString()])
        ->assertNotFound();
});

// Issue #78, criterio 2
test('la pantalla de cierre ofrece reabrir solo al gerente', function () {
    cerrarDiaDeEjemplo($this->sede, today()->toDateString());

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.cierre.show', $this->sede))
        ->assertInertia(fn (Assert $page) => $page->where('auth.permisos.gestionarPos', true)->where('estaCerrado', true));

    $this->actingAs($this->usuarioSede)
        ->get(route('pos.sedes.cierre.show', $this->sede))
        ->assertInertia(fn (Assert $page) => $page->where('auth.permisos.gestionarPos', false)->where('estaCerrado', true));
});

// Issue #78, criterio 3
test('el gerente ve el estado del cierre de todas las sedes en un día', function () {
    $bogota = Sede::factory()->create(['nombre' => 'Aaa Bogotá']);
    $sinCerrar = Sede::factory()->create(['nombre' => 'Zzz Sin cerrar']);
    cerrarDiaDeEjemplo($this->sede, today()->toDateString(), ['valor_a_consignar' => 300000]);
    cerrarDiaDeEjemplo($bogota, today()->toDateString(), ['valor_a_consignar' => 450000, 'valor_diferencia_efectivo' => -5000, 'justificacion' => 'Faltante']);
    CierreCaja::factory()->for($sinCerrar)->create();

    $this->actingAs($this->gerente)
        ->get(route('pos.cierres.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Cierre/Index')
            ->where('fecha', today()->toDateString())
            ->has('filas', 3)
            ->where('filas.0.sede.nombre', 'Aaa Bogotá')
            ->where('filas.0.cierre.valor_a_consignar', '450000.00')
            ->where('filas.0.cierre.valor_diferencia_efectivo', '-5000.00')
            ->where('filas.2.sede.nombre', 'Zzz Sin cerrar')
            ->where('filas.2.cierre', null)
            ->where('valorTotalAConsignar', 750000));
});

// Issue #136, criterio 1
test('una sede inactiva con el día sin cerrar no aparece en el consolidado', function () {
    $inactivaAbierta = Sede::factory()->create(['nombre' => 'Inactiva abierta', 'es_activa' => false]);
    $inactivaCerrada = Sede::factory()->create(['nombre' => 'Inactiva cerrada', 'es_activa' => false]);
    CierreCaja::factory()->for($inactivaAbierta)->create(['fecha' => today()]);
    cerrarDiaDeEjemplo($inactivaCerrada, today()->toDateString());

    $this->actingAs($this->gerente)
        ->get(route('pos.cierres.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('filas', 2)
            ->where('filas.0.sede.nombre', 'Inactiva cerrada')
            ->where('filas.1.sede.id', $this->sede->id));
});

// Issue #78, criterio 3
test('la lista de cierres filtra por día y valida la fecha', function () {
    cerrarDiaDeEjemplo($this->sede, today()->subDay()->toDateString(), ['valor_a_consignar' => 123000]);

    $this->actingAs($this->gerente)
        ->get(route('pos.cierres.index', ['fecha' => today()->subDay()->toDateString()]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filas.0.cierre.valor_a_consignar', '123000.00')
            ->where('valorTotalAConsignar', 123000));

    $this->actingAs($this->gerente)
        ->get(route('pos.cierres.index', ['fecha' => 'ayer']))
        ->assertSessionHasErrors('fecha');
});

// Issue #78, criterio 2
test('el usuario de una sede no ve el consolidado de cierres', function () {
    $this->actingAs($this->usuarioSede)->get(route('pos.cierres.index'))->assertForbidden();
});
