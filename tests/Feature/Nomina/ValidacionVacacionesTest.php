<?php

use App\Actions\Nomina\CalcularSaldoVacaciones;
use App\Actions\Nomina\LiquidarPeriodoNomina;
use App\Enums\ConceptoSistema;
use App\Enums\Rol;
use App\Enums\TipoNovedad;
use App\Models\Contrato;
use App\Models\Empleado;
use App\Models\Novedad;
use App\Models\PeriodoNomina;
use App\Models\ReciboNomina;
use App\Models\Sede;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\NominaSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-12-31 12:00:00', 'America/Bogota'));
    config([
        'vacaciones.zona_horaria' => 'America/Bogota',
        'vacaciones.dias_habiles' => [1, 2, 3, 4, 5, 6],
        'vacaciones.excluir_festivos_colombianos' => true,
        'vacaciones.festivos' => [],
        'vacaciones.es_calendario_confirmado' => true,
    ]);
    $this->sede = Sede::factory()->create();
    $this->usuario = User::factory()->enSede($this->sede, Rol::AuxiliarNomina)->create();
    $this->empleado = Empleado::factory()->create(['sede_id' => $this->sede->id]);
    $this->contrato = Contrato::factory()->for($this->empleado)->create(['fecha_inicio' => '2026-01-01']);
    $this->datos = [
        'empleado_id' => $this->empleado->id,
        'tipo' => TipoNovedad::Vacaciones->value,
        'fecha_inicio' => '2026-12-07',
        'fecha_fin' => '2026-12-12',
    ];
    $this->consulta = fn (array $datos = []) => route('nomina.empleados.vacaciones-novedad', [
        'empleado' => $this->empleado->id, ...$datos,
    ]);
    $this->saldo = fn () => app(CalcularSaldoVacaciones::class)->ejecutar($this->empleado);
    $this->actingAs($this->usuario);
});

afterEach(function () {
    $this->travelBack();
});

// Issue #191, criterios 1 y 2: consulta compartida por el formulario de alta y edición.
test('el formulario consulta saldo y días hábiles sin registrar consumo', function () {
    $this->get(route('nomina.novedades.create'))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Nomina/Novedades/Create')->where('empleados.0.id', $this->empleado->id));
    $this->getJson(($this->consulta)())->assertOk()
        ->assertJsonPath('saldoVacaciones.diasDisponibles', '15.0000')
        ->assertJsonPath('diasDisponiblesParaProgramar', '15.0000')
        ->assertJsonPath('diasADescontar', null);
    $this->getJson(($this->consulta)($this->datos))->assertOk()
        ->assertJsonPath('diasSolicitados', 5) // Incluye sábado 12, excluye el festivo del 8.
        ->assertJsonPath('diasADescontar', 5)
        ->assertJsonPath('diasRestantes', '10.0000')
        ->assertJsonPath('tieneSaldoSuficiente', true);

    expect(Novedad::count())->toBe(0);
});

// Issue #191, criterio 2: usa el calendario central, incluidos festivos trasladados y adicionales.
test('la consulta respeta domingos sábados y festivos', function (string $inicio, string $fin, int $dias) {
    $this->getJson(($this->consulta)(['fecha_inicio' => $inicio, 'fecha_fin' => $fin]))
        ->assertOk()->assertJsonPath('diasSolicitados', $dias)->assertJsonPath('diasADescontar', $dias);
})->with([
    'sábado hábil' => ['2026-01-10', '2026-01-10', 1],
    'domingo' => ['2026-01-11', '2026-01-11', 0],
    'festivo trasladado' => ['2026-01-12', '2026-01-12', 0],
    'rango inclusivo' => ['2026-01-09', '2026-01-13', 3],
]);

test('la consulta y el guardado usan los festivos adicionales configurados de la #191', function () {
    config(['vacaciones.festivos' => ['2026-12-09']]);
    $this->getJson(($this->consulta)($this->datos))->assertJsonPath('diasADescontar', 4);
    $this->post(route('nomina.novedades.store'), $this->datos)->assertSessionHasNoErrors();
    expect(($this->saldo)()['diasUtilizados'])->toBe(4);
});

// Issue #191, criterio 3: validación real del servidor, aunque no se haya consultado el formulario.
test('el servidor rechaza saldo insuficiente sin guardar novedad ni auditoría de consumo', function () {
    $datos = [...$this->datos, 'fecha_inicio' => '2026-01-02', 'fecha_fin' => '2026-01-21'];
    $this->postJson(route('nomina.novedades.store'), $datos)
        ->assertUnprocessable()->assertJsonValidationErrors('fecha_fin')
        ->assertJsonPath('errors.fecha_fin.0', 'Saldo de vacaciones insuficiente. Disponibles para programar: 15.0000 días hábiles; este período requiere 16 días hábiles adicionales.');

    expect(Novedad::count())->toBe(0);
    $this->assertDatabaseMissing('auditorias', ['auditable_type' => Novedad::class]);
    expect(($this->saldo)()['diasDisponibles'])->toBe('15.0000');
});

// Issue #191, criterios 3 y 4: no redondear una fracción a un día ni anticipar causación futura.
test('no permite gastar un día con saldo fraccionario aunque se solicite en el futuro', function () {
    $this->contrato->update(['fecha_inicio' => '2026-12-31']);
    $this->postJson(route('nomina.novedades.store'), [
        ...$this->datos, 'fecha_inicio' => '2028-01-04', 'fecha_fin' => '2028-01-04',
    ])->assertUnprocessable()->assertJsonValidationErrors('fecha_fin');
    expect(($this->saldo)()['diasDisponibles'])->toBe('0.0411')->and(Novedad::count())->toBe(0);
});

// Issue #191, criterio 4: edición sin duplicar el consumo propio, rechazo atómico y eliminación.
test('permite agotar el saldo editar sin descontar dos veces y devolver días al eliminar', function () {
    $datos = [...$this->datos, 'fecha_inicio' => '2026-01-02', 'fecha_fin' => '2026-01-20'];
    $this->post(route('nomina.novedades.store'), $datos)->assertSessionHasNoErrors();
    $novedad = Novedad::sole();
    expect(($this->saldo)()['diasDisponibles'])->toBe('0.0000');

    $this->get(route('nomina.novedades.edit', $novedad))->assertOk();
    $this->getJson(($this->consulta)([...$datos, 'novedad_id' => $novedad->id]))
        ->assertOk()->assertJsonPath('diasDisponiblesParaProgramar', '15.0000')
        ->assertJsonPath('diasADescontar', 15)->assertJsonPath('tieneSaldoSuficiente', true);
    $this->put(route('nomina.novedades.update', $novedad), [...$datos, 'observacion' => 'Confirmadas'])
        ->assertSessionHasNoErrors();
    expect(($this->saldo)()['diasDisponibles'])->toBe('0.0000');

    $this->putJson(route('nomina.novedades.update', $novedad), [...$datos, 'fecha_fin' => '2026-01-21'])
        ->assertUnprocessable()->assertJsonValidationErrors('fecha_fin');
    expect($novedad->fresh()->fecha_fin->toDateString())->toBe('2026-01-20');

    $this->put(route('nomina.novedades.update', $novedad), [...$datos, 'fecha_fin' => '2026-01-09'])
        ->assertSessionHasNoErrors();
    expect(($this->saldo)()['diasDisponibles'])->toBe('8.0000');
    $this->delete(route('nomina.novedades.destroy', $novedad))->assertRedirect();
    expect(($this->saldo)()['diasDisponibles'])->toBe('15.0000');
});

// Issue #191, criterios 3 y 4: todas las reservas futuras cuentan, incluso posteriores a la propuesta.
test('reserva vacaciones futuras sin modificar la consulta histórica de la #188', function () {
    $futuras = [...$this->datos, 'fecha_inicio' => '2027-01-04', 'fecha_fin' => '2027-01-09'];
    $this->post(route('nomina.novedades.store'), $futuras)->assertSessionHasNoErrors();
    $novedad = Novedad::sole();
    expect(($this->saldo)()['diasDisponibles'])->toBe('15.0000');

    $this->getJson(($this->consulta)())->assertJsonPath('diasProgramados', 6)
        ->assertJsonPath('diasDisponiblesParaProgramar', '9.0000');
    foreach ([
        ['fecha_inicio' => '2027-01-12', 'fecha_fin' => '2027-01-23'],
        ['fecha_inicio' => '2026-01-02', 'fecha_fin' => '2026-01-14'],
    ] as $rango) {
        $this->postJson(route('nomina.novedades.store'), [...$this->datos, ...$rango])
            ->assertUnprocessable()->assertJsonValidationErrors('fecha_fin');
    }

    $this->put(route('nomina.novedades.update', $novedad), $futuras)->assertSessionHasNoErrors();
    $this->delete(route('nomina.novedades.destroy', $novedad))->assertRedirect();
    $this->getJson(($this->consulta)())->assertJsonPath('diasDisponiblesParaProgramar', '15.0000');
});

// Issue #191, criterio 5: unión de intervalos antes de validar, guardar y devolver consumo.
test('solapamientos parciales y totales consumen solo días nuevos y no devuelven días compartidos', function () {
    $this->post(route('nomina.novedades.store'), $this->datos)->assertSessionHasNoErrors();
    $primera = Novedad::sole();
    $solapada = [...$this->datos, 'fecha_inicio' => '2026-12-10', 'fecha_fin' => '2026-12-15'];
    $this->getJson(($this->consulta)($solapada))
        ->assertJsonPath('diasSolicitados', 5)->assertJsonPath('diasADescontar', 2);
    $this->post(route('nomina.novedades.store'), $solapada)->assertSessionHasNoErrors();
    $segunda = Novedad::latest('id')->firstOrFail();
    $this->post(route('nomina.novedades.store'), $solapada)->assertSessionHasNoErrors();
    expect(($this->saldo)()['diasUtilizados'])->toBe(7);
    $this->put(route('nomina.novedades.update', $segunda), $solapada)->assertSessionHasNoErrors();
    expect(($this->saldo)()['diasUtilizados'])->toBe(7);
    $this->delete(route('nomina.novedades.destroy', $primera))->assertRedirect();
    expect(($this->saldo)()['diasUtilizados'])->toBe(5);
    $this->delete(route('nomina.novedades.destroy', $segunda))->assertRedirect();
    expect(($this->saldo)()['diasUtilizados'])->toBe(5);
});

test('la #191 permite un intervalo ya cubierto aunque no queden días disponibles', function () {
    $datos = [...$this->datos, 'fecha_inicio' => '2026-01-02', 'fecha_fin' => '2026-01-20'];
    $this->post(route('nomina.novedades.store'), $datos)->assertSessionHasNoErrors();
    $this->getJson(($this->consulta)($datos))->assertJsonPath('diasADescontar', 0)
        ->assertJsonPath('tieneSaldoSuficiente', true);
    $this->post(route('nomina.novedades.store'), $datos)->assertSessionHasNoErrors();
    expect(($this->saldo)()['diasUtilizados'])->toBe(15);
});

// Issue #191, criterio 4: cambiar de tipo o empleado actualiza únicamente los saldos afectados.
test('cambiar de tipo libera vacaciones y volver a vacaciones exige saldo suficiente', function () {
    $this->post(route('nomina.novedades.store'), $this->datos)->assertSessionHasNoErrors();
    $novedad = Novedad::sole();
    $this->put(route('nomina.novedades.update', $novedad), [...$this->datos, 'tipo' => TipoNovedad::Incapacidad->value])
        ->assertSessionHasNoErrors();
    expect(($this->saldo)()['diasUtilizados'])->toBe(0);
    $this->contrato->update(['fecha_inicio' => '2026-12-01']);
    $this->putJson(route('nomina.novedades.update', $novedad), $this->datos)
        ->assertUnprocessable()->assertJsonValidationErrors('fecha_fin');
    expect($novedad->fresh()->tipo)->toBe(TipoNovedad::Incapacidad);
});

test('cambiar de empleado valida el saldo del destino sin liberar el original si falla en la #191', function () {
    $otro = Empleado::factory()->create(['sede_id' => $this->sede->id]);
    $contrato = Contrato::factory()->for($otro)->create(['fecha_inicio' => '2026-12-01']);
    $this->post(route('nomina.novedades.store'), $this->datos)->assertSessionHasNoErrors();
    $novedad = Novedad::sole();
    $datos = [...$this->datos, 'empleado_id' => $otro->id];
    $this->putJson(route('nomina.novedades.update', $novedad), $datos)
        ->assertUnprocessable()->assertJsonValidationErrors('fecha_fin');
    expect(($this->saldo)()['diasUtilizados'])->toBe(5);
    $contrato->update(['fecha_inicio' => '2026-01-01']);
    $this->put(route('nomina.novedades.update', $novedad), $datos)->assertSessionHasNoErrors();
    expect(($this->saldo)()['diasUtilizados'])->toBe(0)
        ->and(app(CalcularSaldoVacaciones::class)->ejecutar($otro)['diasUtilizados'])->toBe(5);
});

// Issue #191, criterios 1-4: no amplía permisos ni permite excluir novedades de otra sede.
test('los roles de nómina solo consultan y modifican vacaciones de su sede', function (Rol $rol) {
    $this->actingAs(User::factory()->enSede($this->sede, $rol)->create());
    $ajeno = Empleado::factory()->create(['sede_id' => Sede::factory()->create()->id]);
    $novedadAjena = Novedad::factory()->for($ajeno)->create(['tipo' => TipoNovedad::Vacaciones]);
    $this->getJson(($this->consulta)())->assertOk();
    $this->getJson(route('nomina.empleados.vacaciones-novedad', $ajeno))->assertNotFound();
    $this->getJson(($this->consulta)(['novedad_id' => $novedadAjena->id]))->assertNotFound();
    $this->postJson(route('nomina.novedades.store'), [...$this->datos, 'empleado_id' => $ajeno->id])
        ->assertUnprocessable()->assertJsonValidationErrors('empleado_id');
    $this->putJson(route('nomina.novedades.update', $novedadAjena), $this->datos)->assertNotFound();
    $this->deleteJson(route('nomina.novedades.destroy', $novedadAjena))->assertNotFound();
})->with([Rol::Administrador, Rol::AuxiliarNomina]);

test('la consulta de la #191 exige permiso y sede salvo para superadmin', function (?Rol $rol, int $estado) {
    $this->actingAs(User::factory()->create(['rol' => $rol, 'sede_id' => null]));
    $this->getJson(($this->consulta)())->assertStatus($estado);
})->with([
    'superadmin global' => [Rol::SuperAdmin, 200],
    'administrador sin sede' => [Rol::Administrador, 404],
    'auxiliar sin sede' => [Rol::AuxiliarNomina, 404],
    'cajero' => [Rol::Cajero, 403],
    'contador' => [Rol::Contador, 403],
    'sin rol' => [null, 403],
]);

test('un visitante no consulta el saldo de la #191', function () {
    auth()->logout();
    $this->getJson(($this->consulta)())->assertUnauthorized();
});

test('la #191 mantiene las validaciones de fechas y empleados activos', function () {
    foreach ([
        ['fecha_inicio' => 'incorrecta', 'fecha_fin' => '2026-12-12'],
        ['fecha_inicio' => '2026-12-12', 'fecha_fin' => '2026-12-01'],
        ['fecha_inicio' => '2026-12-12'],
    ] as $datos) {
        $this->getJson(($this->consulta)($datos))->assertUnprocessable();
    }
    $this->empleado->update(['es_activo' => false]);
    $this->postJson(route('nomina.novedades.store'), $this->datos)
        ->assertUnprocessable()->assertJsonValidationErrors('empleado_id');
});

// Issue #191, criterio 6: mismos importes, días calendario y bloqueos de liquidación (sin #193).
test('liquidar y reliquidar preserva importes calendario y consumo hábil sin desbloquear novedades', function () {
    $this->seed(NominaSeeder::class);
    $this->contrato->update(['valor_salario_base' => '2100000.00']);
    $datos = [...$this->datos, 'fecha_inicio' => '2026-02-06', 'fecha_fin' => '2026-02-09'];
    $this->post(route('nomina.novedades.store'), $datos)->assertSessionHasNoErrors();
    $novedad = Novedad::sole();
    $saldo = ($this->saldo)();
    expect($novedad->dias())->toBe(4)->and($saldo['diasUtilizados'])->toBe(3);
    $periodo = PeriodoNomina::factory()->create([
        'sede_id' => $this->sede->id, 'fecha_inicio' => '2026-02-01', 'fecha_fin' => '2026-02-28',
    ]);

    app(LiquidarPeriodoNomina::class)->ejecutar($periodo, $this->usuario);
    $recibo = ReciboNomina::sole();
    $valores = $recibo->detalles()->with('concepto')->get()
        ->mapWithKeys(fn ($detalle) => [$detalle->concepto->codigo => $detalle->valor_concepto])->all();
    expect($valores)->toMatchArray([
        ConceptoSistema::Vacaciones->value => '280000.00',
        ConceptoSistema::Salario->value => '1820000.00',
    ])->and($recibo->dias_liquidados)->toBe(26);
    $totales = $recibo->only(['valor_total_devengado', 'valor_total_deducciones', 'valor_neto']);

    app(LiquidarPeriodoNomina::class)->ejecutar($periodo->fresh(), $this->usuario);
    expect(($this->saldo)())->toBe($saldo)
        ->and(ReciboNomina::sole()->only(array_keys($totales)))->toBe($totales)
        ->and(Novedad::count())->toBe(1);

    $this->get(route('nomina.novedades.edit', $novedad))->assertForbidden();
    $this->getJson(($this->consulta)(['novedad_id' => $novedad->id]))->assertForbidden();
    $this->putJson(route('nomina.novedades.update', $novedad), $datos)->assertForbidden();
    $this->deleteJson(route('nomina.novedades.destroy', $novedad))->assertForbidden();
    expect(($this->saldo)())->toBe($saldo);
});

// Issue #191, criterios 3 y 5: la validación ignora una vista previa desactualizada.
test('revalida el saldo al guardar después de otra solicitud y deduplica reservas futuras', function () {
    $datos = [...$this->datos, 'fecha_inicio' => '2027-01-04', 'fecha_fin' => '2027-01-09'];
    $posterior = [...$this->datos, 'fecha_inicio' => '2027-01-12', 'fecha_fin' => '2027-01-23'];
    $this->getJson(($this->consulta)($posterior))->assertJsonPath('tieneSaldoSuficiente', true);
    $this->post(route('nomina.novedades.store'), $datos)->assertSessionHasNoErrors();
    $this->post(route('nomina.novedades.store'), $datos)->assertSessionHasNoErrors();
    $this->getJson(($this->consulta)())->assertJsonPath('diasProgramados', 6)
        ->assertJsonPath('diasDisponiblesParaProgramar', '9.0000');
    $this->postJson(route('nomina.novedades.store'), $posterior)
        ->assertUnprocessable()->assertJsonValidationErrors('fecha_fin');
    expect(Novedad::count())->toBe(2);
});

// Issue #191, criterios 2-4: separar lo consumido hasta hoy del resto del mismo intervalo.
test('una novedad que cruza el corte reserva únicamente su parte futura', function () {
    $datos = [...$this->datos, 'fecha_inicio' => '2026-12-30', 'fecha_fin' => '2027-01-04'];
    $this->getJson(($this->consulta)($datos))->assertJsonPath('diasSolicitados', 4)
        ->assertJsonPath('diasADescontar', 4);
    $this->post(route('nomina.novedades.store'), $datos)->assertSessionHasNoErrors();
    expect(($this->saldo)()['diasUtilizados'])->toBe(2)
        ->and(($this->saldo)()['diasDisponibles'])->toBe('13.0000');
    $this->getJson(($this->consulta)())->assertJsonPath('diasProgramados', 2)
        ->assertJsonPath('diasDisponiblesParaProgramar', '11.0000');
});

// Issue #191, criterios 1 y 2: conserva el recorte al servicio y los avisos de la #188.
test('la vista previa distingue días del período y consumo dentro del servicio válido', function () {
    $this->contrato->update(['fecha_inicio' => '2026-01-10', 'fecha_fin' => '2026-12-31']);
    $this->getJson(($this->consulta)(['fecha_inicio' => '2026-01-09', 'fecha_fin' => '2026-01-13']))
        ->assertJsonPath('diasSolicitados', 3)->assertJsonPath('diasADescontar', 2);
    $this->contrato->delete();
    $this->getJson(($this->consulta)())->assertJsonPath('saldoVacaciones.fechaIngreso', null)
        ->assertJsonPath('diasDisponiblesParaProgramar', '0.0000');
});
