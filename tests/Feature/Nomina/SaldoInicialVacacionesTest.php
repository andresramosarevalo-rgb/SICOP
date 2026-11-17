<?php

use App\Actions\Nomina\CalcularSaldoVacaciones;
use App\Actions\Nomina\LiquidarPeriodoNomina;
use App\Enums\ConceptoSistema;
use App\Enums\Rol;
use App\Enums\TipoNovedad;
use App\Models\Auditoria;
use App\Models\Contrato;
use App\Models\Empleado;
use App\Models\Novedad;
use App\Models\PeriodoNomina;
use App\Models\ReciboNomina;
use App\Models\Sede;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\NominaSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
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
    $this->contrato = Contrato::factory()->for($this->empleado)->create(['fecha_inicio' => '2025-01-01']);
    $this->referencia = ['dias_vacaciones_historicos' => 12, 'fecha_corte_vacaciones' => '2025-12-31'];
    $this->ruta = route('nomina.empleados.saldo-inicial-vacaciones', $this->empleado);
    $this->saldo = fn (?string $corte = null) => app(CalcularSaldoVacaciones::class)
        ->ejecutar($this->empleado, $corte === null ? null : CarbonImmutable::parse($corte));
    $this->vacaciones = [
        'empleado_id' => $this->empleado->id, 'tipo' => TipoNovedad::Vacaciones->value,
        'fecha_inicio' => '2026-02-06', 'fecha_fin' => '2026-02-09',
    ];
    $this->actingAs($this->usuario);
});

afterEach(function () {
    $this->travelBack();
});

// Issue #192, criterios 1 y 2: el dato es consumo acumulado, no saldo disponible.
test('registra la referencia y presenta causados utilizados y disponibles en el expediente', function () {
    $this->patch($this->ruta, $this->referencia)->assertSessionHasNoErrors()
        ->assertRedirect(route('nomina.empleados.show', $this->empleado));
    $this->assertDatabaseHas('empleados', ['id' => $this->empleado->id, ...$this->referencia]);
    expect(Novedad::count())->toBe(0);
    $this->get(route('nomina.empleados.show', $this->empleado))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Nomina/Empleados/Show')
            ->where('empleado.dias_vacaciones_historicos', 12)
            ->where('empleado.fecha_corte_vacaciones', '2025-12-31')
            ->where('saldoVacaciones.diasCausados', '30.0000')
            ->where('saldoVacaciones.diasUtilizados', 12)
            ->where('saldoVacaciones.diasDisponibles', '18.0000'));
});

// Issue #192, criterios 1 y 2: cero es una referencia explícita.
test('admite cero días históricos y aplica su corte inclusivo', function () {
    Novedad::factory()->for($this->empleado)->create([
        'tipo' => TipoNovedad::Vacaciones, 'fecha_inicio' => '2025-12-30', 'fecha_fin' => '2025-12-31',
    ]);
    $this->patch($this->ruta, [...$this->referencia, 'dias_vacaciones_historicos' => 0])->assertSessionHasNoErrors();
    expect(($this->saldo)()['diasUtilizados'])->toBe(0)
        ->and(($this->saldo)()['diasDisponibles'])->toBe('30.0000');
});

// Issue #192, criterio 1: validación de servidor sin referencias parciales.
test('rechaza datos históricos inválidos sin modificar empleado ni auditoría', function (array $datos, string $campo) {
    $auditorias = Auditoria::count();
    $this->patchJson($this->ruta, $datos)->assertUnprocessable()->assertJsonValidationErrors($campo);
    expect($this->empleado->fresh()->dias_vacaciones_historicos)->toBeNull()
        ->and($this->empleado->fresh()->fecha_corte_vacaciones)->toBeNull()
        ->and(Auditoria::count())->toBe($auditorias);
})->with([
    'sin días' => [['fecha_corte_vacaciones' => '2025-12-31'], 'dias_vacaciones_historicos'],
    'sin corte' => [['dias_vacaciones_historicos' => 12], 'fecha_corte_vacaciones'],
    'negativos' => [['dias_vacaciones_historicos' => -1, 'fecha_corte_vacaciones' => '2025-12-31'], 'dias_vacaciones_historicos'],
    'fraccionarios' => [['dias_vacaciones_historicos' => '1.5', 'fecha_corte_vacaciones' => '2025-12-31'], 'dias_vacaciones_historicos'],
    'texto' => [['dias_vacaciones_historicos' => 'doce', 'fecha_corte_vacaciones' => '2025-12-31'], 'dias_vacaciones_historicos'],
    'fuera del almacenamiento' => [['dias_vacaciones_historicos' => '2147483648', 'fecha_corte_vacaciones' => '2025-12-31'], 'dias_vacaciones_historicos'],
    'fecha imposible' => [['dias_vacaciones_historicos' => 12, 'fecha_corte_vacaciones' => '2026-02-30'], 'fecha_corte_vacaciones'],
    'fecha ambigua' => [['dias_vacaciones_historicos' => 12, 'fecha_corte_vacaciones' => '31/12/2025'], 'fecha_corte_vacaciones'],
    'corte futuro' => [['dias_vacaciones_historicos' => 12, 'fecha_corte_vacaciones' => '2027-01-01'], 'fecha_corte_vacaciones'],
    'vacíos' => [['dias_vacaciones_historicos' => null, 'fecha_corte_vacaciones' => null], 'dias_vacaciones_historicos'],
]);

// Issue #192, criterio 1: mismo día local que #188 y #191.
test('valida el corte con la fecha de Bogotá cerca de medianoche UTC', function () {
    $this->travelTo(CarbonImmutable::parse('2027-01-01 02:00:00', 'UTC'));
    $this->patchJson($this->ruta, [...$this->referencia, 'fecha_corte_vacaciones' => '2027-01-01'])
        ->assertUnprocessable()->assertJsonValidationErrors('fecha_corte_vacaciones');
    $this->patch($this->ruta, [...$this->referencia, 'fecha_corte_vacaciones' => '2026-12-31'])->assertSessionHasNoErrors();
});

// Issue #192, criterio 2: corte inclusivo, rangos cruzados, duplicados y festivos.
test('deduplica el histórico y las novedades anteriores coincidentes y posteriores al corte', function () {
    $this->patch($this->ruta, [...$this->referencia, 'fecha_corte_vacaciones' => '2026-01-09'])->assertSessionHasNoErrors();
    foreach ([
        ['2026-01-05', '2026-01-09'], ['2026-01-09', null],
        ['2026-01-08', '2026-01-13'], ['2026-01-09', '2026-01-13'], ['2026-01-13', null],
    ] as [$inicio, $fin]) {
        // Registradas después del corte; importan las fechas de disfrute, nunca created_at.
        Novedad::factory()->for($this->empleado)->create([
            'tipo' => TipoNovedad::Vacaciones, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
        ]);
    }
    expect(($this->saldo)()['diasUtilizados'])->toBe(14) // Solo añade sábado 10 y martes 13.
        ->and(($this->saldo)()['diasCausados'])->toBe('30.0000')
        ->and(($this->saldo)()['diasDisponibles'])->toBe('16.0000')
        ->and(($this->saldo)('2026-01-09')['diasUtilizados'])->toBe(12);
});

// Issue #192, criterio 2: el total agregado no reconstruye el pasado.
test('no aplica el histórico a una consulta anterior al corte y advierte la limitación', function () {
    $this->patch($this->ruta, [...$this->referencia, 'fecha_corte_vacaciones' => '2026-01-09'])->assertSessionHasNoErrors();
    Novedad::factory()->for($this->empleado)->create([
        'tipo' => TipoNovedad::Vacaciones, 'fecha_inicio' => '2026-01-08', 'fecha_fin' => '2026-01-13',
    ]);
    $saldo = ($this->saldo)('2026-01-08');
    expect($saldo['diasUtilizados'])->toBe(1)
        ->and(implode(' ', $saldo['advertencias']))->toContain('corte posterior a esta consulta');
});

// Issue #192, criterio 3: se releen días y fecha aunque el empleado esté cargado.
test('corregir la referencia recalcula el consumo sin usar datos o relaciones precargados', function () {
    $this->patch($this->ruta, $this->referencia)->assertSessionHasNoErrors();
    Novedad::factory()->for($this->empleado)->create($this->vacaciones);
    Novedad::factory()->for($this->empleado)->create([
        'tipo' => TipoNovedad::Vacaciones, 'fecha_inicio' => '2026-06-29', 'fecha_fin' => '2026-07-03',
    ]);
    $this->empleado->refresh()->load(['contratos', 'novedades']);
    expect(($this->saldo)()['diasUtilizados'])->toBe(19); // El 29 de junio es festivo.
    $this->patch($this->ruta, ['dias_vacaciones_historicos' => 14, 'fecha_corte_vacaciones' => '2026-07-01'])
        ->assertSessionHasNoErrors();
    expect(($this->saldo)()['diasUtilizados'])->toBe(16)
        ->and(($this->saldo)()['diasDisponibles'])->toBe('14.0000')
        ->and($this->empleado->dias_vacaciones_historicos)->toBe(12)
        ->and($this->empleado->fecha_corte_vacaciones->toDateString())->toBe('2025-12-31');
    $this->patch($this->ruta, $this->referencia)->assertSessionHasNoErrors();
    expect(($this->saldo)()['diasUtilizados'])->toBe(19);
});

// Issue #192, criterios 3 y 4: mismo historial con antes/después, fecha, actor y sede.
test('audita el registro y la corrección sin eventos por guardar lo mismo', function () {
    $this->patch($this->ruta, $this->referencia)->assertSessionHasNoErrors();
    $otroUsuario = User::factory()->enSede($this->sede)->create();
    $correccion = ['dias_vacaciones_historicos' => 10, 'fecha_corte_vacaciones' => '2026-01-01'];
    $this->actingAs($otroUsuario)->patch($this->ruta, $correccion)->assertSessionHasNoErrors();
    $this->patch($this->ruta, $correccion)->assertSessionHasNoErrors();
    $auditorias = Auditoria::where('auditable_type', Empleado::class)->where('auditable_id', $this->empleado->id)
        ->where('evento', 'modificado')->orderBy('id')->get();
    expect($auditorias)->toHaveCount(2)
        ->and($auditorias[0]->user_id)->toBe($this->usuario->id)
        ->and($auditorias[0]->cambios['dias_vacaciones_historicos'])->toBe(['antes' => null, 'despues' => 12])
        ->and($auditorias[0]->cambios['fecha_corte_vacaciones'])->toBe(['antes' => null, 'despues' => '2025-12-31'])
        ->and($auditorias[1]->user_id)->toBe($otroUsuario->id)
        ->and($auditorias[1]->modulo)->toBe('nomina')
        ->and($auditorias[1]->sede_id)->toBe($this->sede->id)
        ->and($auditorias[1]->created_at->equalTo(now()))->toBeTrue()
        ->and($auditorias[1]->cambios['dias_vacaciones_historicos'])->toBe(['antes' => 12, 'despues' => 10])
        ->and($auditorias[1]->cambios['fecha_corte_vacaciones'])->toBe(['antes' => '2025-12-31', 'despues' => '2026-01-01']);
});

// Issue #192, criterios 3 y 4: fallo de auditoría revierte ambos campos.
test('una corrección no queda guardada si falla su auditoría', function () {
    $this->patch($this->ruta, $this->referencia)->assertSessionHasNoErrors();
    $auditorias = Auditoria::count();
    Auditoria::creating(function (): void {
        throw new RuntimeException('Fallo simulado de auditoría');
    });
    $this->withoutExceptionHandling();
    expect(fn () => $this->patch($this->ruta, [
        'dias_vacaciones_historicos' => 8, 'fecha_corte_vacaciones' => '2026-01-31',
    ]))->toThrow(RuntimeException::class, 'Fallo simulado de auditoría');
    $this->assertDatabaseHas('empleados', ['id' => $this->empleado->id, ...$this->referencia]);
    expect(Auditoria::count())->toBe($auditorias);
});

// Issue #192, criterio 1: PostgreSQL impide pares incompletos o negativos.
test('la base de datos impide referencias parciales o negativas', function (array $datos) {
    expect(fn () => DB::transaction(fn () => DB::table('empleados')
        ->where('id', $this->empleado->id)->update($datos)))->toThrow(QueryException::class);
    expect($this->empleado->fresh()->dias_vacaciones_historicos)->toBeNull()
        ->and($this->empleado->fresh()->fecha_corte_vacaciones)->toBeNull();
})->with([
    [['dias_vacaciones_historicos' => 12]],
    [['fecha_corte_vacaciones' => '2025-12-31']],
    [['dias_vacaciones_historicos' => -1, 'fecha_corte_vacaciones' => '2025-12-31']],
]);

// Issue #192, criterio 5: permisos de nómina existentes.
test('los roles de nómina registran y corrigen la referencia en su ámbito', function (Rol $rol) {
    $this->actingAs(User::factory()->enSede($this->sede, $rol)->create());
    $this->patch($this->ruta, $this->referencia)->assertSessionHasNoErrors();
    $this->patch($this->ruta, [...$this->referencia, 'dias_vacaciones_historicos' => 10])->assertSessionHasNoErrors();
    expect($this->empleado->fresh()->dias_vacaciones_historicos)->toBe(10);
})->with([Rol::AuxiliarNomina, Rol::Administrador, Rol::SuperAdmin]);

// Issue #192, criterio 5: cambiar URL o cuerpo no evade la sede.
test('impide registrar o corregir referencias de otra sede y de empleados sin sede', function (Rol $rol) {
    $this->actingAs(User::factory()->enSede($this->sede, $rol)->create());
    foreach ([Sede::factory()->create()->id, null] as $sedeId) {
        $otro = Empleado::factory()->create(['sede_id' => $sedeId]);
        $ruta = route('nomina.empleados.saldo-inicial-vacaciones', $otro);
        $this->patchJson($ruta, [...$this->referencia, 'sede_id' => $this->sede->id])->assertNotFound();
        $otro->update($this->referencia);
        $auditorias = Auditoria::count();
        $this->patchJson($ruta, [...$this->referencia, 'dias_vacaciones_historicos' => 1])->assertNotFound();
        expect($otro->fresh()->dias_vacaciones_historicos)->toBe(12)->and(Auditoria::count())->toBe($auditorias);
    }
})->with([Rol::Administrador, Rol::AuxiliarNomina]);

// Issue #192, criterio 5: Superadmin conserva su alcance global.
test('el super administrador corrige referencias de cualquier sede y sin sede', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    foreach ([Sede::factory()->create()->id, null] as $sedeId) {
        $empleado = Empleado::factory()->create(['sede_id' => $sedeId]);
        $ruta = route('nomina.empleados.saldo-inicial-vacaciones', $empleado);
        $this->patch($ruta, $this->referencia)->assertSessionHasNoErrors();
        $this->patch($ruta, [...$this->referencia, 'dias_vacaciones_historicos' => 7])->assertSessionHasNoErrors();
        expect($empleado->fresh()->dias_vacaciones_historicos)->toBe(7);
    }
});

// Issue #192, criterio 5: ausencia de sede nunca concede acceso global.
test('los usuarios de nómina sin sede no registran referencias', function (Rol $rol) {
    $this->actingAs(User::factory()->conRol($rol)->create(['sede_id' => null]))
        ->patchJson($this->ruta, $this->referencia)->assertNotFound();
    expect($this->empleado->fresh()->dias_vacaciones_historicos)->toBeNull();
})->with([Rol::Administrador, Rol::AuxiliarNomina]);

// Issue #192, criterio 5.
test('rechaza usuarios sin permiso aunque pertenezcan a la sede', function (?Rol $rol) {
    $this->actingAs(User::factory()->create(['rol' => $rol, 'sede_id' => $this->sede->id]));
    $this->patchJson($this->ruta, $this->referencia)->assertForbidden();
    $this->empleado->update($this->referencia);
    $auditorias = Auditoria::count();
    $this->patchJson($this->ruta, [...$this->referencia, 'dias_vacaciones_historicos' => 1])->assertForbidden();
    expect($this->empleado->fresh()->dias_vacaciones_historicos)->toBe(12)->and(Auditoria::count())->toBe($auditorias);
})->with([Rol::Contador, Rol::Cajero, null]);

// Issue #192, criterio 5.
test('los visitantes deben iniciar sesión para registrar la referencia', function () {
    auth()->logout();
    $this->patch($this->ruta, $this->referencia)->assertRedirect(route('login'));
    expect($this->empleado->fresh()->dias_vacaciones_historicos)->toBeNull();
});

// Issue #192, criterios 1 y 5: endpoint limitado a los dos campos del historial.
test('ignora campos ajenos al historial enviados junto con la referencia', function () {
    $this->patch($this->ruta, [
        ...$this->referencia, 'sede_id' => Sede::factory()->create()->id, 'es_activo' => false, 'nombres' => 'Alterado',
    ])->assertSessionHasNoErrors();
    expect($this->empleado->fresh()->sede_id)->toBe($this->sede->id)
        ->and($this->empleado->fresh()->es_activo)->toBeTrue()
        ->and($this->empleado->fresh()->nombres)->toBe($this->empleado->nombres);
});

// Issue #192, criterio 2: ausencia de historial mantiene #188.
test('sin referencia conserva la causación y el consumo de las novedades existentes', function () {
    Novedad::factory()->for($this->empleado)->create($this->vacaciones);
    expect(($this->saldo)()['diasCausados'])->toBe('30.0000')
        ->and(($this->saldo)()['diasUtilizados'])->toBe(3)
        ->and(($this->saldo)()['diasDisponibles'])->toBe('27.0000');
});

// Issue #192, criterios 2 y 3: no inventa servicio ni oculta resultados negativos.
test('conserva el consumo histórico aunque no haya servicio registrado', function () {
    $this->contrato->delete();
    $this->patch($this->ruta, $this->referencia)->assertSessionHasNoErrors();
    expect(($this->saldo)()['fechaIngreso'])->toBeNull()
        ->and(($this->saldo)()['diasCausados'])->toBe('0.0000')
        ->and(($this->saldo)()['diasUtilizados'])->toBe(12)
        ->and(($this->saldo)()['diasDisponibles'])->toBe('-12.0000');
});

// Issue #192, criterio 2: #191 usa el histórico en registro, edición y devolución.
test('la validación de novedades usa el histórico al registrar editar y eliminar vacaciones', function () {
    $this->patch($this->ruta, [...$this->referencia, 'dias_vacaciones_historicos' => 27])->assertSessionHasNoErrors();
    $excesiva = [...$this->vacaciones, 'fecha_fin' => '2026-02-10'];
    $this->getJson(route('nomina.empleados.vacaciones-novedad', ['empleado' => $this->empleado->id, ...$excesiva]))
        ->assertJsonPath('diasDisponiblesParaProgramar', '3.0000')->assertJsonPath('tieneSaldoSuficiente', false);
    $this->postJson(route('nomina.novedades.store'), $excesiva)->assertUnprocessable()->assertJsonValidationErrors('fecha_fin');
    $this->post(route('nomina.novedades.store'), $this->vacaciones)->assertSessionHasNoErrors();
    $novedad = Novedad::sole();
    expect(($this->saldo)()['diasDisponibles'])->toBe('0.0000');
    $this->put(route('nomina.novedades.update', $novedad), $this->vacaciones)->assertSessionHasNoErrors();
    expect(($this->saldo)()['diasUtilizados'])->toBe(30);
    $this->put(route('nomina.novedades.update', $novedad), [...$this->vacaciones, 'fecha_fin' => '2026-02-06'])
        ->assertSessionHasNoErrors();
    expect(($this->saldo)()['diasUtilizados'])->toBe(28);
    $this->delete(route('nomina.novedades.destroy', $novedad))->assertRedirect();
    expect(($this->saldo)()['diasUtilizados'])->toBe(27);
});

// Issue #192, criterio 2: una novedad absorbida por el corte no devuelve histórico.
test('registrar editar o eliminar novedades anteriores al corte no altera el consumo histórico', function () {
    $this->patch($this->ruta, [...$this->referencia, 'fecha_corte_vacaciones' => '2026-02-09'])->assertSessionHasNoErrors();
    $this->post(route('nomina.novedades.store'), $this->vacaciones)->assertSessionHasNoErrors();
    $novedad = Novedad::sole();
    expect(($this->saldo)()['diasUtilizados'])->toBe(12);
    $this->put(route('nomina.novedades.update', $novedad), [...$this->vacaciones, 'fecha_fin' => '2026-02-06'])
        ->assertSessionHasNoErrors();
    expect(($this->saldo)()['diasUtilizados'])->toBe(12);
    $this->delete(route('nomina.novedades.destroy', $novedad))->assertRedirect();
    expect(($this->saldo)()['diasUtilizados'])->toBe(12);
});

// Issue #192, criterios 2 y 3: vistas previas y reservas de #191 reflejan correcciones.
test('las reservas futuras y las vistas previas se recalculan tras corregir el histórico', function () {
    $this->patch($this->ruta, [...$this->referencia, 'dias_vacaciones_historicos' => 24])->assertSessionHasNoErrors();
    $futura = [...$this->vacaciones, 'fecha_inicio' => '2027-01-04', 'fecha_fin' => '2027-01-09'];
    $this->getJson(route('nomina.empleados.vacaciones-novedad', ['empleado' => $this->empleado->id, ...$futura]))
        ->assertJsonPath('tieneSaldoSuficiente', true);
    $this->patch($this->ruta, [...$this->referencia, 'dias_vacaciones_historicos' => 25])->assertSessionHasNoErrors();
    $this->postJson(route('nomina.novedades.store'), $futura)->assertUnprocessable()->assertJsonValidationErrors('fecha_fin');
    $this->patch($this->ruta, [...$this->referencia, 'dias_vacaciones_historicos' => 24])->assertSessionHasNoErrors();
    $this->post(route('nomina.novedades.store'), $futura)->assertSessionHasNoErrors();
    expect(($this->saldo)()['diasDisponibles'])->toBe('6.0000');
    $this->getJson(route('nomina.empleados.vacaciones-novedad', $this->empleado))
        ->assertJsonPath('diasProgramados', 6)->assertJsonPath('diasDisponiblesParaProgramar', '0.0000');
    $this->patch($this->ruta, [...$this->referencia, 'dias_vacaciones_historicos' => 25])->assertSessionHasNoErrors();
    $this->getJson(route('nomina.empleados.vacaciones-novedad', $this->empleado))
        ->assertJsonPath('diasProgramados', 6)->assertJsonPath('diasDisponiblesParaProgramar', '-1.0000');
    expect(Novedad::count())->toBe(1);
});

// Issue #192, criterio 2 y exclusiones: conserva importes, días calendario y bloqueos.
test('registrar y corregir el histórico conserva la liquidación monetaria y sus bloqueos', function () {
    $this->seed(NominaSeeder::class);
    $this->contrato->update(['valor_salario_base' => '2100000.00']);
    Novedad::factory()->for($this->empleado)->create($this->vacaciones);
    $periodo = PeriodoNomina::factory()->create([
        'sede_id' => $this->sede->id, 'fecha_inicio' => '2026-02-01', 'fecha_fin' => '2026-02-28',
    ]);
    $liquidar = app(LiquidarPeriodoNomina::class);
    $liquidar->ejecutar($periodo, $this->usuario);
    $recibo = ReciboNomina::sole();
    $totales = $recibo->only(['dias_liquidados', 'valor_total_devengado', 'valor_total_deducciones', 'valor_neto']);
    $detalles = $recibo->detalles()->with('concepto')->get()
        ->mapWithKeys(fn ($detalle) => [$detalle->concepto->codigo => $detalle->valor_concepto])->all();
    expect($detalles)->toMatchArray([
        ConceptoSistema::Vacaciones->value => '280000.00',
        ConceptoSistema::Salario->value => '1820000.00',
    ]);
    foreach ([12, 14] as $historico) {
        $this->patch($this->ruta, [
            'dias_vacaciones_historicos' => $historico, 'fecha_corte_vacaciones' => '2026-02-09',
        ])->assertSessionHasNoErrors();
        $liquidar->ejecutar($periodo->fresh(), $this->usuario);
        expect(ReciboNomina::sole()->only(array_keys($totales)))->toBe($totales)
            ->and(ReciboNomina::sole()->detalles()->with('concepto')->get()
                ->mapWithKeys(fn ($detalle) => [$detalle->concepto->codigo => $detalle->valor_concepto])->all())->toBe($detalles)
            ->and(($this->saldo)()['diasUtilizados'])->toBe($historico);
    }
    $novedad = Novedad::sole();
    expect($novedad->dias())->toBe(4);
    $this->putJson(route('nomina.novedades.update', $novedad), $this->vacaciones)->assertForbidden();
    $this->deleteJson(route('nomina.novedades.destroy', $novedad))->assertForbidden();
});
