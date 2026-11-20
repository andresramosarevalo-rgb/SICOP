<?php

use App\Enums\EstadoCierre;
use App\Enums\Rol;
use App\Models\CierreCaja;
use App\Models\Curso;
use App\Models\GastoCaja;
use App\Models\Sede;
use App\Models\Soat;
use App\Models\TarifaGestor;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gerente = User::factory()->conRol(Rol::SuperAdmin)->create();
    $this->sede = Sede::factory()->create(['valor_base_caja' => 200000]);
    $this->usuarioSede = User::factory()->paraSede($this->sede)->create();

    Curso::factory()->for($this->sede)->create(['fecha' => today(), 'valor_curso' => 300000, 'forma_pago' => 'efectivo', 'valor_efectivo' => 300000, 'valor_pago_gestor' => 20000]);
    Curso::factory()->for($this->sede)->create(['fecha' => today(), 'valor_curso' => 250000, 'forma_pago' => 'mixto', 'valor_efectivo' => 100000, 'valor_datafono' => 100000, 'valor_qr' => 50000, 'valor_pago_gestor' => 10000]);
    Soat::factory()->for($this->sede)->create(['fecha' => today(), 'valor_soat' => 580000, 'forma_pago' => 'mixto', 'valor_efectivo' => 280000, 'valor_qr' => 300000]);
    // efectivo ventas 680.000 · datáfono 100.000 · QR 350.000 · gestores 30.000
    // esperado = 200.000 + 680.000 − 30.000 − gastos
});

function datosCierre(array $sobrescribir = []): array
{
    return [
        'fecha' => today()->toDateString(),
        ...$sobrescribir,
    ];
}

function datosCursoDelDia(): array
{
    return [
        'fecha' => today()->toDateString(),
        'tipo_documento' => 'CC',
        'numero_documento' => '1006123456',
        'nombre_cliente' => 'Carlos Pérez',
        'celular' => '3101234567',
        'correo' => 'carlos@example.com',
        'numero_certificado' => 'CERT-001',
        'numero_comparendo' => '50001000000012345678',
        'secretaria_transito' => 'Secretaría de Tránsito de Villavicencio',
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
        'codigo_infraccion' => 'C',
        'valor_curso' => '350000',
        'porcentaje_descuento' => 50,
        'forma_pago' => 'efectivo',
    ];
}

function datosSoatDelDia(): array
{
    return [
        'fecha' => today()->toDateString(),
        'tipo_documento' => 'CC',
        'numero_documento' => '1006123456',
        'nombre_cliente' => 'Carlos Pérez',
        'celular' => '3101234567',
        'correo' => 'carlos@example.com',
        'placa' => 'ABC123',
        'valor_soat' => '580000',
        'forma_pago' => 'efectivo',
    ];
}

// Issue #77, criterio 1
test('la sede cierra el día sin digitar conteos y se congelan las cifras del sistema', function () {
    $this->actingAs($this->usuarioSede)
        ->post(route('pos.sedes.cierre.store', $this->sede), datosCierre())
        ->assertRedirect(route('pos.sedes.cierre.show', ['sede' => $this->sede, 'fecha' => today()->toDateString()]))
        ->assertSessionHasNoErrors();

    expect(CierreCaja::firstOrFail())
        ->estado->toBe(EstadoCierre::Cerrado)
        ->valor_base_caja->toBe('200000.00')
        ->valor_efectivo_ventas->toBe('680000.00')
        ->valor_pago_gestores->toBe('30000.00')
        ->valor_gastos->toBe('0.00')
        ->valor_efectivo_esperado->toBe('850000.00')
        ->valor_efectivo_contado->toBe('850000.00')
        ->valor_reportado_datafono->toBe('100000.00')
        ->valor_reportado_qr->toBe('350000.00')
        ->valor_diferencia_efectivo->toBe('0.00')
        ->valor_diferencia_datafono->toBe('0.00')
        ->valor_diferencia_qr->toBe('0.00')
        ->valor_a_consignar->toBe('650000.00')
        ->justificacion->toBeNull()
        ->cerrado_por->toBe($this->usuarioSede->id)
        ->cerrado_en->not->toBeNull();
});

// Issue #77, criterio 1
test('los gastos y los gestores salen del efectivo esperado y el sistema calcula lo que se consigna', function () {
    $cierre = CierreCaja::factory()->for($this->sede)->create();
    GastoCaja::factory()->for($cierre)->create(['valor_gasto' => 50000]);

    $this->actingAs($this->usuarioSede)
        ->post(route('pos.sedes.cierre.store', $this->sede), datosCierre())
        ->assertSessionHasNoErrors();

    expect($cierre->fresh())
        ->valor_gastos->toBe('50000.00')
        ->valor_efectivo_esperado->toBe('800000.00')
        ->valor_diferencia_efectivo->toBe('0.00')
        ->valor_a_consignar->toBe('600000.00');
});

test('el cierre ya no pide efectivo contado, datáfono ni QR', function () {
    $this->actingAs($this->usuarioSede)
        ->post(route('pos.sedes.cierre.store', $this->sede), ['fecha' => today()->toDateString()])
        ->assertSessionHasNoErrors();

    expect(CierreCaja::firstOrFail())
        ->estado->toBe(EstadoCierre::Cerrado)
        ->valor_diferencia_efectivo->toBe('0.00')
        ->valor_diferencia_datafono->toBe('0.00')
        ->valor_diferencia_qr->toBe('0.00');
});

// Issue #77, criterios 3 y 5
test('no se puede cerrar un día futuro ni cerrar dos veces el mismo día', function () {
    $this->actingAs($this->usuarioSede)
        ->post(route('pos.sedes.cierre.store', $this->sede), datosCierre(['fecha' => today()->addDay()->toDateString()]))
        ->assertSessionHasErrors('fecha');

    $this->actingAs($this->usuarioSede)->post(route('pos.sedes.cierre.store', $this->sede), datosCierre());
    $this->actingAs($this->usuarioSede)
        ->post(route('pos.sedes.cierre.store', $this->sede), datosCierre())
        ->assertForbidden();

    expect(CierreCaja::count())->toBe(1);
});

// Issue #77, criterio 5
test('se puede cerrar un día anterior que quedó sin cerrar', function () {
    $ayer = today()->subDay();
    Curso::factory()->for($this->sede)->create(['fecha' => $ayer, 'valor_efectivo' => 100000, 'valor_pago_gestor' => 0]);

    $this->actingAs($this->usuarioSede)
        ->post(route('pos.sedes.cierre.store', $this->sede), datosCierre(['fecha' => $ayer->toDateString()]))
        ->assertSessionHasNoErrors();

    expect(CierreCaja::whereDate('fecha', $ayer)->first())
        ->estado->toBe(EstadoCierre::Cerrado)
        ->valor_efectivo_esperado->toBe('300000.00');
});

// Issue #77, criterio 4
test('las cifras cerradas no cambian aunque cambie la tarifa o se editen las ventas después', function () {
    $this->actingAs($this->usuarioSede)->post(route('pos.sedes.cierre.store', $this->sede), datosCierre());

    TarifaGestor::factory()->create(['valor_pago_gestor' => 99000]);
    Curso::query()->update(['valor_pago_gestor' => 99000, 'valor_efectivo' => 1]);

    $this->actingAs($this->gerente)
        ->get(route('pos.sedes.cierre.show', $this->sede))
        ->assertInertia(fn (Assert $page) => $page
            ->where('estaCerrado', true)
            ->where('resumen.valor_efectivo_ventas', '680000.00')
            ->where('resumen.valor_pago_gestores', '30000.00')
            ->where('valorEfectivoEsperado', 850000)
            ->where('cierre.valor_a_consignar', '650000.00')
            ->where('cierre.cerrado_por.name', $this->usuarioSede->name));
});

// Issue #77, criterio 3
test('un día cerrado bloquea registrar y editar cursos y SOAT de esa fecha', function () {
    $curso = Curso::first();
    $soat = Soat::first();
    $this->actingAs($this->usuarioSede)->post(route('pos.sedes.cierre.store', $this->sede), datosCierre());

    $this->actingAs($this->usuarioSede)
        ->post(route('pos.sedes.cursos.store', $this->sede), datosCursoDelDia())
        ->assertForbidden();
    $this->actingAs($this->usuarioSede)
        ->post(route('pos.sedes.soats.store', $this->sede), datosSoatDelDia())
        ->assertForbidden();
    $this->actingAs($this->usuarioSede)->get(route('pos.cursos.edit', $curso))->assertForbidden();
    $this->actingAs($this->usuarioSede)->put(route('pos.cursos.update', $curso), datosCursoDelDia())->assertForbidden();
    $this->actingAs($this->usuarioSede)->put(route('pos.soats.update', $soat), datosSoatDelDia())->assertForbidden();

    expect(Curso::count())->toBe(2)->and(Soat::count())->toBe(1);
});

// Issue #77, criterio 3
test('un día cerrado no impide registrar en otra fecha ni mover un registro hacia el día cerrado', function () {
    $this->actingAs($this->usuarioSede)->post(route('pos.sedes.cierre.store', $this->sede), datosCierre());
    $ayer = today()->subDay();
    $curso = Curso::factory()->for($this->sede)->create(['fecha' => $ayer]);

    $this->actingAs($this->usuarioSede)
        ->post(route('pos.sedes.cursos.store', $this->sede), array_merge(datosCursoDelDia(), ['fecha' => $ayer->toDateString()]))
        ->assertSessionHasNoErrors();

    $this->actingAs($this->usuarioSede)
        ->put(route('pos.cursos.update', $curso), datosCursoDelDia())
        ->assertForbidden();
});

// Issue #77, criterio 3
test('el cierre de otra sede no bloquea esta sede', function () {
    $otraSede = Sede::factory()->create();
    CierreCaja::factory()->cerrado()->for($otraSede)->create();

    $this->actingAs($this->usuarioSede)
        ->post(route('pos.sedes.cursos.store', $this->sede), datosCursoDelDia())
        ->assertSessionHasNoErrors();
});

// Issue #77, criterio 5
test('una sede inactiva no cierra la caja y el usuario de otra sede tampoco', function () {
    $this->sede->update(['es_activa' => false]);

    $this->actingAs($this->gerente)->post(route('pos.sedes.cierre.store', $this->sede), datosCierre())->assertForbidden();

    $otra = User::factory()->paraSede(Sede::factory()->create())->create();
    $this->sede->update(['es_activa' => true]);
    $this->actingAs($otra)->post(route('pos.sedes.cierre.store', $this->sede), datosCierre())->assertForbidden();
});

// Issue #77, criterio 1
test('el gerente también puede cerrar la caja de una sede', function () {
    $this->actingAs($this->gerente)
        ->post(route('pos.sedes.cierre.store', $this->sede), datosCierre())
        ->assertSessionHasNoErrors();

    expect(CierreCaja::first()->cerrado_por)->toBe($this->gerente->id);
});
