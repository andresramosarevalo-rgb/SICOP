<?php

use App\Enums\Rol;
use App\Models\CierreCaja;
use App\Models\Consignacion;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gerente = User::factory()->conRol(Rol::Administrador)->create();
    $this->sede = Sede::factory()->create();
    $this->usuarioSede = User::factory()->paraSede($this->sede)->create();
    $this->cierre = CierreCaja::factory()->cerrado()->for($this->sede)->create(['fecha' => today()->subDay(), 'valor_a_consignar' => 300000]);
});

// Issue #80, criterio 1
test('una consignación parcial deja el resto como saldo pendiente', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.cierre.consignacion.store', $this->cierre), ['fecha_consignacion' => today()->toDateString(), 'valor_consignado' => '250000', 'referencia' => 'REF-002', 'soporte' => UploadedFile::fake()->create('comprobante.pdf', 100, 'application/pdf')])
        ->assertSessionHasNoErrors();

    $this->actingAs($this->gerente)
        ->get(route('pos.consignaciones.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filas.0.valor_a_consignar', '300000.00')
            ->where('filas.0.valor_consignado', '250000.00'));
});

// Issue #80, criterios 1 y 2
test('el saldo por sede suma los días cerrados y cuenta los días sin consignar', function () {
    CierreCaja::factory()->cerrado()->for($this->sede)->create(['fecha' => today()->subDays(2), 'valor_a_consignar' => 100000]);
    CierreCaja::factory()->cerrado()->for($this->sede)->create(['fecha' => today()->subDays(3), 'valor_a_consignar' => 0]);
    CierreCaja::factory()->for($this->sede)->create(['fecha' => today(), 'valor_a_consignar' => null]);
    Consignacion::factory()->create(['cierre_caja_id' => $this->cierre->id, 'valor_consignado' => 300000]);

    $otra = Sede::factory()->create(['nombre' => 'Zzz otra']);
    CierreCaja::factory()->cerrado()->for($otra)->create(['valor_a_consignar' => 50000]);

    $this->actingAs($this->gerente)
        ->get(route('pos.consignaciones.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Consignaciones/Index')
            ->has('filas', 2)
            ->where('filas.0.sede.id', $this->sede->id)
            ->where('filas.0.valor_a_consignar', '400000.00')
            ->where('filas.0.valor_consignado', '300000.00')
            ->where('filas.0.dias_sin_consignar', 1)
            ->where('filas.1.valor_a_consignar', '50000.00')
            ->where('filas.1.valor_consignado', '0.00')
            ->where('filas.1.dias_sin_consignar', 1)
            ->where('sedeId', null)
            ->has('dias', 0));
});

// Issue #80, criterio 3
test('al elegir una sede muestra sus días cerrados con el estado de la consignación', function () {
    $consignado = CierreCaja::factory()->cerrado()->for($this->sede)->create(['fecha' => today()->subDays(2), 'valor_a_consignar' => 100000]);
    Consignacion::factory()->create(['cierre_caja_id' => $consignado->id, 'referencia' => 'REF-XYZ']);

    $this->actingAs($this->gerente)
        ->get(route('pos.consignaciones.index', ['sede_id' => $this->sede->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sedeId', $this->sede->id)
            ->has('dias', 2)
            ->where('dias.0.fecha', today()->subDay()->toDateString())
            ->where('dias.0.consignacion', null)
            ->where('dias.1.consignacion.referencia', 'REF-XYZ'));
});

// Issue #135, criterio 1
test('el detalle de la sede incluye los días antiguos que aún están sin consignar', function () {
    $antiguo = CierreCaja::factory()->cerrado()->for($this->sede)->create(['fecha' => today()->subDays(200), 'valor_a_consignar' => 50000]);
    $consignadoAntiguo = CierreCaja::factory()->cerrado()->for($this->sede)->create(['fecha' => today()->subDays(201), 'valor_a_consignar' => 50000]);
    Consignacion::factory()->create(['cierre_caja_id' => $consignadoAntiguo->id]);

    foreach (range(2, 91) as $atras) {
        CierreCaja::factory()->cerrado()->for($this->sede)->create(['fecha' => today()->subDays($atras), 'valor_a_consignar' => 0]);
    }

    $this->actingAs($this->gerente)
        ->get(route('pos.consignaciones.index', ['sede_id' => $this->sede->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('dias', 91)
            ->where('dias.90.id', $antiguo->id));
});

// Issue #80, criterio 4
test('el usuario de la sede no ve los saldos de consignación', function () {
    $this->actingAs($this->usuarioSede)->get(route('pos.consignaciones.index'))->assertForbidden();
});
