<?php

use App\Actions\Nomina\LiquidarPeriodoNomina;
use App\Enums\EstadoPeriodo;
use App\Enums\Rol;
use App\Mail\ReciboNominaMail;
use App\Models\Contrato;
use App\Models\PeriodoNomina;
use App\Models\ReciboNomina;
use App\Models\Sede;
use App\Models\User;
use Database\Seeders\NominaSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Mail::fake();
    $this->seed(NominaSeeder::class);
    $this->usuario = User::factory()->superAdmin()->create();
    $this->contrato = Contrato::factory()->create(['valor_salario_base' => '1750905']);
    $this->periodo = PeriodoNomina::factory()->create();
    $this->liquidar = fn () => app(LiquidarPeriodoNomina::class)->ejecutar($this->periodo->fresh(), $this->usuario);
});

// Issue #189, criterio 1
test('al liquidar de nuevo, el recibo anterior y sus detalles quedan conservados', function () {
    ($this->liquidar)();
    $primero = ReciboNomina::vigentes()->sole();
    $detallesPrimero = $primero->detalles()->count();

    $this->contrato->update(['valor_salario_base' => '2000000']);
    ($this->liquidar)();

    $anterior = $primero->fresh();

    expect(ReciboNomina::count())->toBe(2)
        ->and($anterior->es_vigente)->toBeFalse()
        ->and($anterior->reemplazado_en)->not->toBeNull()
        ->and($anterior->valor_salario_base)->toBe('1750905.00')
        ->and($anterior->detalles()->count())->toBe($detallesPrimero)
        ->and(ReciboNomina::vigentes()->sole()->valor_salario_base)->toBe('2000000.00');
});

// Issue #189, criterio 2
test('un usuario autorizado distingue el resultado anterior del vigente en el periodo y en cada comprobante', function () {
    ($this->liquidar)();
    $primero = ReciboNomina::vigentes()->sole();
    $this->contrato->update(['valor_salario_base' => '2000000']);
    ($this->liquidar)();
    $vigente = ReciboNomina::vigentes()->sole();

    $this->actingAs($this->usuario)
        ->get(route('nomina.periodos.show', $this->periodo))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Nomina/Periodos/Show')
            ->has('recibos', 1)
            ->where('recibos.0.id', $vigente->id)
            ->has('recibosAnteriores', 1)
            ->where('recibosAnteriores.0.id', $primero->id)
            ->where('recibosAnteriores.0.es_vigente', false));

    $this->actingAs($this->usuario)
        ->get(route('nomina.recibos.show', $primero))
        ->assertInertia(fn (Assert $page) => $page
            ->where('recibo.es_vigente', false)
            ->whereNot('recibo.reemplazado_en', null));

    $this->actingAs($this->usuario)
        ->get(route('nomina.recibos.show', $vigente))
        ->assertInertia(fn (Assert $page) => $page
            ->where('recibo.es_vigente', true)
            ->where('recibo.reemplazado_en', null));
});

// Issue #189, criterio 3
test('un comprobante ya enviado conserva su envío y no se confunde con el vigente al liquidar de nuevo', function () {
    ($this->liquidar)();
    $enviado = ReciboNomina::vigentes()->sole();
    $enviado->forceFill(['fecha_envio' => now()->subDay()])->save();

    ($this->liquidar)();
    $vigente = ReciboNomina::vigentes()->sole();

    expect($enviado->fresh()->fecha_envio)->not->toBeNull()
        ->and($enviado->fresh()->es_vigente)->toBeFalse()
        ->and($vigente->fecha_envio)->toBeNull();

    $this->periodo->forceFill(['estado' => EstadoPeriodo::Cerrado])->save();

    $this->actingAs($this->usuario)
        ->post(route('nomina.recibos.reenviar', $enviado))
        ->assertSessionHasErrors('periodo');
    Mail::assertNothingQueued();

    $this->actingAs($this->usuario)->post(route('nomina.periodos.enviar', $this->periodo));
    Mail::assertQueued(ReciboNominaMail::class, 1);
    Mail::assertQueued(ReciboNominaMail::class, fn (ReciboNominaMail $correo) => $correo->recibo->is($vigente));
});

// Issue #189, criterio 4
test('los totales del periodo cuentan solo los comprobantes vigentes', function () {
    ($this->liquidar)();
    $this->contrato->update(['valor_salario_base' => '2000000']);
    ($this->liquidar)();
    $vigente = ReciboNomina::vigentes()->sole();

    $this->actingAs($this->usuario)
        ->get(route('nomina.periodos.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('periodos.0.recibos_count', 1)
            ->where('periodos.0.recibos_sum_valor_neto', $vigente->valor_neto));

    expect($this->periodo->recibos()->count())->toBe(1)
        ->and($this->periodo->recibosAnteriores()->count())->toBe(1);
});

// Issue #189, criterio 4
test('no puede haber dos recibos vigentes del mismo empleado en un periodo', function () {
    ($this->liquidar)();
    $recibo = ReciboNomina::vigentes()->sole();

    expect(fn () => ReciboNomina::factory()->create([
        'periodo_nomina_id' => $recibo->periodo_nomina_id,
        'contrato_id' => $recibo->contrato_id,
        'empleado_id' => $recibo->empleado_id,
    ]))->toThrow(QueryException::class);
});

// Issue #189, criterio 5
test('un fallo durante la nueva liquidación no deja un historial parcial ni sustituye el resultado válido', function () {
    ($this->liquidar)();
    $original = ReciboNomina::vigentes()->sole();
    $estadoAntes = $this->periodo->fresh()->estado;

    ReciboNomina::creating(fn () => throw new RuntimeException('Fallo simulado al generar el recibo.'));

    try {
        expect(fn () => ($this->liquidar)())->toThrow(RuntimeException::class);
    } finally {
        Event::forget('eloquent.creating: '.ReciboNomina::class);
    }

    expect(ReciboNomina::count())->toBe(1)
        ->and($original->fresh()->es_vigente)->toBeTrue()
        ->and($original->fresh()->reemplazado_en)->toBeNull()
        ->and($this->periodo->fresh()->estado)->toBe($estadoAntes);
});

// Issue #189: restricciones de acceso por sede
test('el historial de otra sede no se puede consultar', function () {
    $sedeA = Sede::factory()->create();
    $sedeB = Sede::factory()->create();
    $this->contrato->empleado->update(['sede_id' => $sedeA->id]);
    $periodoA = PeriodoNomina::factory()->create(['sede_id' => $sedeA->id, 'fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-31']);
    app(LiquidarPeriodoNomina::class)->ejecutar($periodoA, $this->usuario);
    app(LiquidarPeriodoNomina::class)->ejecutar($periodoA->fresh(), $this->usuario);
    $anterior = ReciboNomina::query()->where('es_vigente', false)->sole();

    $this->actingAs(User::factory()->enSede($sedeA, Rol::AuxiliarNomina)->create())
        ->get(route('nomina.recibos.show', $anterior))
        ->assertOk();

    $this->actingAs(User::factory()->enSede($sedeB, Rol::AuxiliarNomina)->create())
        ->get(route('nomina.recibos.show', $anterior))
        ->assertNotFound();
});
