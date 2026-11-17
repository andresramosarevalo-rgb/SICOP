<?php

use App\Actions\Nomina\CalcularSaldoVacaciones;
use App\Actions\Nomina\LiquidarPeriodoNomina;
use App\Enums\Rol;
use App\Enums\TipoNovedad;
use App\Models\Contrato;
use App\Models\Empleado;
use App\Models\Novedad;
use App\Models\PeriodoNomina;
use App\Models\Sede;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\NominaSeeder;
use Illuminate\Support\Facades\DB;

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
        'fecha_inicio' => '2026-12-01',
        'fecha_fin' => '2026-12-05',
    ];
    $this->actingAs($this->usuario);
    $this->post(route('nomina.novedades.store'), $this->datos)->assertSessionHasNoErrors();
    $this->novedad = Novedad::sole();

    // Un ajuste del contrato deja negativo un consumo registrado con saldo suficiente.
    $this->contrato->update(['fecha_inicio' => '2026-12-01']);
    $this->saldo = fn () => app(CalcularSaldoVacaciones::class)->ejecutar($this->empleado);
    $this->consulta = fn (array $datos) => route('nomina.empleados.vacaciones-novedad', [
        'empleado' => $this->empleado->id, ...$datos,
    ]);
    expect(($this->saldo)()['diasDisponibles'])->toBe('-3.7260');
});

afterEach(function () {
    $this->travelBack();
});

// Issue #191: el déficit previo no bloquea una edición que conserva o reduce el consumo.
test('permite editar vacaciones sin aumentar el consumo aunque el saldo siga negativo', function (string $inicio, string $fin, int $consumo, string $saldo) {
    $datos = [...$this->datos, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin, 'observacion' => 'Corrección autorizada'];
    $this->getJson(($this->consulta)([...$datos, 'novedad_id' => $this->novedad->id]))
        ->assertOk()->assertJsonPath('tieneSaldoSuficiente', true)->assertJsonPath('diasRestantes', $saldo);
    $this->put(route('nomina.novedades.update', $this->novedad), $datos)->assertSessionHasNoErrors();

    expect(Novedad::count())->toBe(1)
        ->and($this->novedad->fresh()->observacion)->toBe('Corrección autorizada')
        ->and(($this->saldo)()['diasUtilizados'])->toBe($consumo)
        ->and(($this->saldo)()['diasDisponibles'])->toBe($saldo);
    $this->assertDatabaseHas('auditorias', [
        'auditable_type' => Novedad::class, 'auditable_id' => $this->novedad->id, 'evento' => 'modificado',
    ]);
})->with([
    'solo observación' => ['2026-12-01', '2026-12-05', 5, '-3.7260'],
    'reduce dos días' => ['2026-12-01', '2026-12-03', 3, '-1.7260'],
    'reprograma el mismo consumo' => ['2026-12-09', '2026-12-14', 5, '-3.7260'],
]);

test('rechaza aumentar el consumo con saldo negativo sin modificar la novedad ni su auditoría', function () {
    $datos = [...$this->datos, 'fecha_fin' => '2026-12-07'];
    $auditorias = DB::table('auditorias')->count();
    $this->getJson(($this->consulta)([...$datos, 'novedad_id' => $this->novedad->id]))
        ->assertJsonPath('tieneSaldoSuficiente', false)->assertJsonPath('diasRestantes', '-4.7260');
    $this->putJson(route('nomina.novedades.update', $this->novedad), $datos)
        ->assertUnprocessable()->assertJsonValidationErrors('fecha_fin');
    expect($this->novedad->fresh()->fecha_fin->toDateString())->toBe('2026-12-05')
        ->and(DB::table('auditorias')->count())->toBe($auditorias)
        ->and(($this->saldo)()['diasDisponibles'])->toBe('-3.7260');
});

// Issue #191: la excepción de edición no permite nuevas novedades de cero consumo.
test('mantiene el rechazo de nuevas vacaciones con déficit incluso si no agregan días', function (string $inicio, string $fin, int $adicionales) {
    $datos = [...$this->datos, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin];
    $this->getJson(($this->consulta)($datos))->assertJsonPath('diasADescontar', $adicionales)
        ->assertJsonPath('tieneSaldoSuficiente', false);
    $this->postJson(route('nomina.novedades.store'), $datos)
        ->assertUnprocessable()->assertJsonValidationErrors('fecha_fin');
    expect(Novedad::count())->toBe(1);
})->with([
    'intervalo ya cubierto' => ['2026-12-01', '2026-12-05', 0],
    'domingo sin consumo hábil' => ['2026-12-06', '2026-12-06', 0],
    'día adicional' => ['2026-12-07', '2026-12-07', 1],
]);

// Issue #191: se compara la unión real, no la duración aislada de la novedad.
test('permite ampliar una novedad si todos los días añadidos ya están cubiertos', function () {
    Novedad::factory()->for($this->empleado)->create([
        'tipo' => TipoNovedad::Vacaciones, 'fecha_inicio' => '2026-12-03', 'fecha_fin' => '2026-12-07',
    ]);
    expect(($this->saldo)()['diasUtilizados'])->toBe(6);
    $datos = [...$this->datos, 'fecha_fin' => '2026-12-07'];
    $this->getJson(($this->consulta)([...$datos, 'novedad_id' => $this->novedad->id]))
        ->assertJsonPath('diasSolicitados', 6)->assertJsonPath('diasADescontar', 2)
        ->assertJsonPath('tieneSaldoSuficiente', true);
    $this->put(route('nomina.novedades.update', $this->novedad), $datos)->assertSessionHasNoErrors();
    expect(($this->saldo)()['diasUtilizados'])->toBe(6);
});

test('rechaza una reprogramación de igual duración que pierde solapamientos y aumenta el total', function () {
    Novedad::factory()->for($this->empleado)->create([
        'tipo' => TipoNovedad::Vacaciones, 'fecha_inicio' => '2026-12-03', 'fecha_fin' => '2026-12-07',
    ]);
    $datos = [...$this->datos, 'fecha_inicio' => '2026-12-09', 'fecha_fin' => '2026-12-14'];
    $this->getJson(($this->consulta)([...$datos, 'novedad_id' => $this->novedad->id]))
        ->assertJsonPath('diasSolicitados', 5)->assertJsonPath('tieneSaldoSuficiente', false);
    $this->putJson(route('nomina.novedades.update', $this->novedad), $datos)
        ->assertUnprocessable()->assertJsonValidationErrors('fecha_fin');
    expect(($this->saldo)()['diasUtilizados'])->toBe(6);
});

// Issue #191: el horizonte incluye la fecha original, aunque el reemplazo termine antes.
test('permite adelantar una reserva reduciendo el total sin perder reservas posteriores', function () {
    $this->novedad->update(['fecha_inicio' => '2027-02-01', 'fecha_fin' => '2027-02-06']);
    Novedad::factory()->for($this->empleado)->create([
        'tipo' => TipoNovedad::Vacaciones, 'fecha_inicio' => '2027-01-12', 'fecha_fin' => '2027-01-16',
    ]);
    $this->getJson(($this->consulta)([]))->assertJsonPath('diasProgramados', 11)
        ->assertJsonPath('diasDisponiblesParaProgramar', '-9.7260');
    $this->getJson(($this->consulta)([...$this->datos, 'novedad_id' => $this->novedad->id]))
        ->assertJsonPath('tieneSaldoSuficiente', true)->assertJsonPath('diasRestantes', '-8.7260');
    $this->put(route('nomina.novedades.update', $this->novedad), $this->datos)->assertSessionHasNoErrors();
    $this->getJson(($this->consulta)([]))->assertJsonPath('diasProgramados', 5)
        ->assertJsonPath('saldoVacaciones.diasUtilizados', 5)
        ->assertJsonPath('diasDisponiblesParaProgramar', '-8.7260');
});

test('las reservas futuras siguen impidiendo aumentar el consumo al editar', function () {
    $this->contrato->update(['fecha_inicio' => '2026-01-01']);
    Novedad::factory()->for($this->empleado)->create([
        'tipo' => TipoNovedad::Vacaciones, 'fecha_inicio' => '2027-01-12', 'fecha_fin' => '2027-01-23',
    ]);
    $this->getJson(($this->consulta)([]))->assertJsonPath('saldoVacaciones.diasDisponibles', '10.0000')
        ->assertJsonPath('diasProgramados', 11)->assertJsonPath('diasDisponiblesParaProgramar', '-1.0000');
    $datos = [...$this->datos, 'fecha_fin' => '2026-12-07'];
    $this->putJson(route('nomina.novedades.update', $this->novedad), $datos)
        ->assertUnprocessable()->assertJsonValidationErrors('fecha_fin');
    $this->put(route('nomina.novedades.update', $this->novedad), $this->datos)->assertSessionHasNoErrors();
});

// Issue #191: no se transfiere el crédito de edición entre empleados o tipos.
test('cambiar a un empleado en déficit no permite evadir saldo aunque el destino ya cubra los días', function () {
    $otro = Empleado::factory()->create(['sede_id' => $this->sede->id]);
    Contrato::factory()->for($otro)->create(['fecha_inicio' => '2026-12-01']);
    Novedad::factory()->for($otro)->create([
        'tipo' => TipoNovedad::Vacaciones, 'fecha_inicio' => '2026-12-01', 'fecha_fin' => '2026-12-05',
    ]);
    $datos = [...$this->datos, 'empleado_id' => $otro->id];
    $this->getJson(route('nomina.empleados.vacaciones-novedad', [
        'empleado' => $otro->id, ...$datos, 'novedad_id' => $this->novedad->id,
    ]))->assertJsonPath('diasADescontar', 0)->assertJsonPath('tieneSaldoSuficiente', false);
    $this->putJson(route('nomina.novedades.update', $this->novedad), $datos)
        ->assertUnprocessable()->assertJsonValidationErrors('fecha_fin');
    expect($this->novedad->fresh()->empleado_id)->toBe($this->empleado->id)
        ->and(($this->saldo)()['diasUtilizados'])->toBe(5)
        ->and(app(CalcularSaldoVacaciones::class)->ejecutar($otro)['diasUtilizados'])->toBe(5);
});

test('convertir otro tipo a vacaciones mantiene la validación normal aunque su rango ya esté cubierto', function () {
    $otra = Novedad::factory()->for($this->empleado)->create([
        'tipo' => TipoNovedad::Incapacidad, 'fecha_inicio' => '2026-12-01', 'fecha_fin' => '2026-12-05',
    ]);
    $this->putJson(route('nomina.novedades.update', $otra), $this->datos)
        ->assertUnprocessable()->assertJsonValidationErrors('fecha_fin');
    expect($otra->fresh()->tipo)->toBe(TipoNovedad::Incapacidad);
});

// Issue #191: la excepción de saldo no evita permisos ni aislamiento por sede.
test('solo los usuarios autorizados de la sede pueden editar con déficit', function (?Rol $rol, bool $mismaSede, int $estado) {
    $sede = $mismaSede ? $this->sede : Sede::factory()->create();
    $this->actingAs(User::factory()->create(['rol' => $rol, 'sede_id' => $sede->id]));
    $this->putJson(route('nomina.novedades.update', $this->novedad), [
        ...$this->datos, 'observacion' => 'Autorizada',
    ])->assertStatus($estado);
    expect($this->novedad->fresh()->observacion)->toBe($estado === 302 ? 'Autorizada' : null);
})->with([
    'administrador de la sede' => [Rol::Administrador, true, 302],
    'auxiliar de otra sede' => [Rol::AuxiliarNomina, false, 404],
    'administrador de otra sede' => [Rol::Administrador, false, 404],
    'cajero' => [Rol::Cajero, true, 403],
    'sin rol' => [null, true, 403],
]);

test('la edición sin aumento no puede trasladar una novedad a otra sede', function () {
    $otro = Empleado::factory()->create(['sede_id' => Sede::factory()->create()->id]);
    $this->putJson(route('nomina.novedades.update', $this->novedad), [...$this->datos, 'empleado_id' => $otro->id])
        ->assertUnprocessable()->assertJsonValidationErrors('empleado_id');
    expect($this->novedad->fresh()->empleado_id)->toBe($this->empleado->id);
});

test('las vacaciones liquidadas siguen bloqueadas aunque la edición no aumente el consumo negativo', function () {
    $this->seed(NominaSeeder::class);
    $periodo = PeriodoNomina::factory()->create([
        'sede_id' => $this->sede->id, 'fecha_inicio' => '2026-12-01', 'fecha_fin' => '2026-12-31',
    ]);
    app(LiquidarPeriodoNomina::class)->ejecutar($periodo, $this->usuario);
    expect($this->novedad->fresh()->estaLiquidada())->toBeTrue();
    $this->getJson(($this->consulta)([...$this->datos, 'novedad_id' => $this->novedad->id]))->assertForbidden();
    $this->putJson(route('nomina.novedades.update', $this->novedad), $this->datos)->assertForbidden();
    $this->deleteJson(route('nomina.novedades.destroy', $this->novedad))->assertForbidden();
    expect(($this->saldo)()['diasDisponibles'])->toBe('-3.7260')->and(Novedad::count())->toBe(1);
});
