<?php

use App\Actions\Nomina\LiquidarPeriodoNomina;
use App\Enums\ConceptoSistema;
use App\Enums\EstadoPeriodo;
use App\Enums\Rol;
use App\Enums\TipoNovedad;
use App\Models\Auditoria;
use App\Models\Contrato;
use App\Models\Novedad;
use App\Models\PeriodoNomina;
use App\Models\ReciboNomina;
use App\Models\Sede;
use App\Models\User;
use Database\Seeders\NominaSeeder;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(NominaSeeder::class);
    $this->admin = User::factory()->superAdmin()->create();
    $this->contrato = Contrato::factory()->create(['valor_salario_base' => '1750905']);
    $this->empleado = $this->contrato->empleado;
    $this->periodo = PeriodoNomina::factory()->create();
    $this->novedad = Novedad::factory()->for($this->empleado)->create([
        'tipo' => TipoNovedad::HoraExtraDiurna, 'fecha_inicio' => '2026-02-10', 'cantidad' => 2,
    ]);

    // Recorrido hasta dejar el periodo cerrado con su novedad ya liquidada.
    $this->liquidar = fn () => app(LiquidarPeriodoNomina::class)->ejecutar($this->periodo->fresh(), $this->admin);
    $this->cerrar = fn () => $this->actingAs($this->admin)->post(route('nomina.periodos.cerrar', $this->periodo));
    $this->reabrir = fn () => $this->actingAs($this->admin)->post(route('nomina.periodos.reabrir', $this->periodo));
    $this->corregir = fn (int $horas) => $this->actingAs($this->admin)->put(route('nomina.novedades.update', $this->novedad), [
        'empleado_id' => $this->empleado->id,
        'tipo' => TipoNovedad::HoraExtraDiurna->value,
        'fecha_inicio' => '2026-02-10',
        'cantidad' => $horas,
    ]);
    $this->valorHorasExtra = fn (ReciboNomina $recibo) => $recibo->detalles()->whereHas('concepto', fn ($query) => $query->where('codigo', ConceptoSistema::HoraExtraDiurna->value))->value('valor_concepto');

    ($this->liquidar)();
    ($this->cerrar)();
});

// Issue #193, criterio 1
test('un usuario autorizado reabre un periodo cerrado y deja constancia de quién y cuándo', function () {
    expect($this->periodo->fresh()->estado)->toBe(EstadoPeriodo::Cerrado);
    $vigente = ReciboNomina::vigentes()->sole();

    ($this->reabrir)()->assertRedirect(route('nomina.periodos.show', $this->periodo));

    $periodo = $this->periodo->fresh();

    expect($periodo->estado)->toBe(EstadoPeriodo::Reabierto)
        ->and($periodo->reabierto_por)->toBe($this->admin->id)
        ->and($periodo->fecha_reapertura)->not->toBeNull()
        ->and($periodo->fecha_cierre)->toBeNull()
        ->and($vigente->fresh()->es_vigente)->toBeTrue();
});

// Issue #193, criterio 1
test('solo se puede reabrir un periodo cerrado', function (EstadoPeriodo $estado) {
    $this->periodo->fresh()->forceFill(['estado' => $estado])->save();

    ($this->reabrir)()->assertSessionHasErrors(['periodo' => 'Solo se puede reabrir un periodo cerrado.']);

    expect($this->periodo->fresh()->estado)->toBe($estado)
        ->and($this->periodo->fresh()->reabierto_por)->toBeNull();
})->with([EstadoPeriodo::Borrador, EstadoPeriodo::Liquidado, EstadoPeriodo::Reabierto]);

// Issue #193, criterio 2
test('se corrige una novedad liquidada del periodo reabierto sin desproteger la de otro periodo cerrado', function () {
    $otroPeriodo = PeriodoNomina::factory()->cerrado()->create(['fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-31']);
    $ajena = Novedad::factory()->for($this->empleado)->liquidada($otroPeriodo)->create(['fecha_inicio' => '2026-03-10']);

    expect($this->novedad->fresh()->estaBloqueada())->toBeTrue();
    ($this->reabrir)();

    ($this->corregir)(1)->assertRedirect(route('nomina.novedades.index'));
    expect($this->novedad->fresh()->cantidad)->toBe(1)
        ->and($this->novedad->fresh()->estaBloqueada())->toBeFalse();

    $this->actingAs($this->admin)
        ->put(route('nomina.novedades.update', $ajena), [
            'empleado_id' => $this->empleado->id, 'tipo' => TipoNovedad::HoraExtraDiurna->value,
            'fecha_inicio' => '2026-03-10', 'cantidad' => 9,
        ])->assertForbidden();
    $this->actingAs($this->admin)->delete(route('nomina.novedades.destroy', $ajena))->assertForbidden();
    expect($ajena->fresh()->cantidad)->not->toBe(9);
});

// Issue #193, criterios 2 y 3
test('las novedades de un periodo reabierto aparecen como editables en el listado', function () {
    ($this->reabrir)();

    $this->actingAs($this->admin)
        ->get(route('nomina.novedades.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('novedades.0.esta_liquidada', true)
            ->where('novedades.0.esta_bloqueada', false));
});

// Issue #193, criterio 3
test('al reliquidar, los comprobantes vigentes reflejan las novedades corregidas y el anterior se conserva', function () {
    $anterior = ReciboNomina::vigentes()->sole();
    $valorAnterior = ($this->valorHorasExtra)($anterior);
    ($this->reabrir)();
    ($this->corregir)(1);

    $this->actingAs($this->admin)->post(route('nomina.periodos.liquidar', $this->periodo))
        ->assertRedirect(route('nomina.periodos.show', $this->periodo));

    $vigente = ReciboNomina::vigentes()->sole();

    expect($this->periodo->fresh()->estado)->toBe(EstadoPeriodo::Liquidado)
        ->and((float) ($this->valorHorasExtra)($vigente))->toBeLessThan((float) $valorAnterior)
        ->and($anterior->fresh()->es_vigente)->toBeFalse()
        ->and(($this->valorHorasExtra)($anterior->fresh()))->toBe($valorAnterior);
});

// Issue #193, criterio 3
test('una novedad nueva dentro de las fechas del periodo reabierto entra en la reliquidación', function () {
    ($this->reabrir)();
    $nueva = Novedad::factory()->for($this->empleado)->create([
        'tipo' => TipoNovedad::HoraExtraDiurna, 'fecha_inicio' => '2026-02-12', 'cantidad' => 1,
    ]);

    ($this->liquidar)();

    expect($nueva->fresh()->periodo_nomina_id)->toBe($this->periodo->id);
});

// Issue #193, criterio 4
test('al cerrar de nuevo el periodo reliquidado vuelven las restricciones habituales', function () {
    ($this->reabrir)();
    ($this->corregir)(1);
    ($this->liquidar)();

    ($this->cerrar)()->assertRedirect(route('nomina.periodos.show', $this->periodo));

    expect($this->periodo->fresh()->estado)->toBe(EstadoPeriodo::Cerrado)
        ->and($this->periodo->fresh()->fecha_cierre)->not->toBeNull()
        ->and($this->novedad->fresh()->estaBloqueada())->toBeTrue();

    ($this->corregir)(1)->assertForbidden();
    expect(fn () => ($this->liquidar)())->toThrow(ValidationException::class);
});

// Issue #193: un periodo reabierto sin reliquidar no se puede cerrar
test('un periodo reabierto no se cierra sin volver a liquidarlo', function () {
    ($this->reabrir)();

    ($this->cerrar)()->assertSessionHasErrors(['periodo' => 'Solo se puede cerrar un periodo liquidado.']);

    expect($this->periodo->fresh()->estado)->toBe(EstadoPeriodo::Reabierto);
});

// Issue #193, criterio 5
test('tras corregir y reliquidar, las liquidaciones anteriores siguen accesibles y se identifica la vigente', function () {
    $anterior = ReciboNomina::vigentes()->sole();
    ($this->reabrir)();
    ($this->corregir)(1);
    ($this->liquidar)();
    $vigente = ReciboNomina::vigentes()->sole();

    $this->actingAs($this->admin)
        ->get(route('nomina.periodos.show', $this->periodo))
        ->assertInertia(fn (Assert $page) => $page
            ->where('periodo.estado', 'liquidado')
            ->where('recibos.0.id', $vigente->id)
            ->where('recibosAnteriores.0.id', $anterior->id));
});

// Issue #193, criterio 6
test('quien no tiene autorización para reabrir recibe 403 y el periodo no cambia', function (Rol $rol) {
    $sede = Sede::factory()->create();
    $this->periodo->update(['sede_id' => $sede->id]);
    $usuario = User::factory()->enSede($sede, $rol)->create();

    $this->actingAs($usuario)->post(route('nomina.periodos.reabrir', $this->periodo))->assertForbidden();

    expect($this->periodo->fresh()->estado)->toBe(EstadoPeriodo::Cerrado);
})->with([Rol::AuxiliarNomina, Rol::Cajero]);

// Issue #193, criterio 6
test('un administrador sin alcance sobre la sede del periodo no puede reabrirlo ni corregir sus novedades', function () {
    $sedeA = Sede::factory()->create();
    $sedeB = Sede::factory()->create();
    $this->empleado->update(['sede_id' => $sedeA->id]);
    $this->periodo->update(['sede_id' => $sedeA->id]);

    $ajeno = User::factory()->enSede($sedeB, Rol::Administrador)->create();
    $this->actingAs($ajeno)->post(route('nomina.periodos.reabrir', $this->periodo))->assertNotFound();
    expect($this->periodo->fresh()->estado)->toBe(EstadoPeriodo::Cerrado);

    $propio = User::factory()->enSede($sedeA, Rol::Administrador)->create();
    $this->actingAs($propio)->post(route('nomina.periodos.reabrir', $this->periodo))->assertRedirect();
    expect($this->periodo->fresh()->estado)->toBe(EstadoPeriodo::Reabierto);

    $this->actingAs($ajeno)->put(route('nomina.novedades.update', $this->novedad), [
        'empleado_id' => $this->empleado->id, 'tipo' => TipoNovedad::HoraExtraDiurna->value,
        'fecha_inicio' => '2026-02-10', 'cantidad' => 1,
    ])->assertNotFound();
    expect($this->novedad->fresh()->cantidad)->toBe(2);
});

// Issue #193, criterio 7
test('la reapertura y la corrección quedan en el historial de auditoría', function () {
    ($this->reabrir)();
    ($this->corregir)(1);

    $reapertura = Auditoria::query()
        ->where('auditable_type', PeriodoNomina::class)->where('auditable_id', $this->periodo->id)
        ->where('evento', 'modificado')->latest('id')->first();
    $correccion = Auditoria::query()
        ->where('auditable_type', Novedad::class)->where('auditable_id', $this->novedad->id)
        ->where('evento', 'modificado')->latest('id')->first();

    expect($reapertura->user_id)->toBe($this->admin->id)
        ->and($reapertura->cambios['estado'])->toBe(['antes' => 'cerrado', 'despues' => 'reabierto'])
        ->and($correccion->user_id)->toBe($this->admin->id)
        ->and($correccion->cambios['cantidad']['despues'])->toEqual(1);
});

// Issue #193: recorrido completo
test('recorrido completo: cerrado, reabierto, corrección, reliquidación y cerrado', function () {
    $estados = [$this->periodo->fresh()->estado];

    ($this->reabrir)();
    $estados[] = $this->periodo->fresh()->estado;
    ($this->corregir)(1);
    ($this->liquidar)();
    $estados[] = $this->periodo->fresh()->estado;
    ($this->cerrar)();
    $estados[] = $this->periodo->fresh()->estado;

    expect($estados)->toBe([EstadoPeriodo::Cerrado, EstadoPeriodo::Reabierto, EstadoPeriodo::Liquidado, EstadoPeriodo::Cerrado])
        ->and(ReciboNomina::count())->toBe(2)
        ->and(ReciboNomina::vigentes()->count())->toBe(1);
});
