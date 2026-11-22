<?php

use App\Actions\Nomina\CalcularSaldoVacaciones;
use App\Actions\Nomina\LiquidarPeriodoNomina;
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
        'vacaciones.dias_habiles' => [1, 2, 3, 4, 5],
        'vacaciones.festivos' => [],
        // Calendario de referencia explícito para estas pruebas de intervalos.
        'vacaciones.excluir_festivos_colombianos' => false,
        'vacaciones.es_calendario_confirmado' => false,
    ]);

    $this->sede = Sede::factory()->create();
    $this->superAdmin = User::factory()->superAdmin()->create();
    $this->empleado = Empleado::factory()->create(['sede_id' => $this->sede->id]);
    $this->contrato = Contrato::factory()->for($this->empleado)->create([
        'fecha_inicio' => '2026-01-01',
    ]);
});

afterEach(function () {
    $this->travelBack();
});

// Issue #188, criterios 1 y 3: un año de servicio y consulta en el expediente.
test('el expediente presenta el saldo de vacaciones calculado y su fecha de corte', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('nomina.empleados.show', $this->empleado))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Nomina/Empleados/Show')
            ->where('saldoVacaciones.fechaCorte', '2026-12-31')
            ->where('saldoVacaciones.fechaIngreso', '2026-01-01')
            ->where('saldoVacaciones.diasCausados', '15.0000')
            ->where('saldoVacaciones.diasUtilizados', 0)
            ->where('saldoVacaciones.diasDisponibles', '15.0000')
            ->has('saldoVacaciones.criterioCalendario')
            ->where('saldoVacaciones.advertencias', fn ($advertencias) => count($advertencias) >= 2));
});

// Issue #188, criterios 2, 3 y 4: registrar, editar y eliminar una novedad actualiza la consulta.
test('el saldo del expediente se actualiza al registrar editar y eliminar vacaciones', function () {
    $datos = [
        'empleado_id' => $this->empleado->id,
        'tipo' => TipoNovedad::Vacaciones->value,
        'fecha_inicio' => '2026-12-07',
        'fecha_fin' => '2026-12-11',
    ];

    $this->actingAs($this->superAdmin)
        ->post(route('nomina.novedades.store'), $datos)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('nomina.novedades.create'));

    $novedad = Novedad::sole();

    $this->get(route('nomina.empleados.show', $this->empleado))
        ->assertInertia(fn (Assert $page) => $page
            ->where('saldoVacaciones.diasCausados', '15.0000')
            ->where('saldoVacaciones.diasUtilizados', 5)
            ->where('saldoVacaciones.diasDisponibles', '10.0000'));

    $this->put(route('nomina.novedades.update', $novedad), [...$datos, 'fecha_fin' => '2026-12-09'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('nomina.novedades.index'));

    $this->get(route('nomina.empleados.show', $this->empleado))
        ->assertInertia(fn (Assert $page) => $page
            ->where('saldoVacaciones.diasUtilizados', 3)
            ->where('saldoVacaciones.diasDisponibles', '12.0000'));

    $this->delete(route('nomina.novedades.destroy', $novedad))
        ->assertRedirect(route('nomina.novedades.index'));

    $this->get(route('nomina.empleados.show', $this->empleado))
        ->assertInertia(fn (Assert $page) => $page
            ->where('saldoVacaciones.diasUtilizados', 0)
            ->where('saldoVacaciones.diasDisponibles', '15.0000'));
});

// Issue #188, criterio 4: las fechas contractuales se leen de nuevo en cada consulta.
test('el expediente recalcula la causación después de corregir la fecha de ingreso contractual', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('nomina.empleados.show', $this->empleado))
        ->assertInertia(fn (Assert $page) => $page->where('saldoVacaciones.diasCausados', '15.0000'));

    $this->contrato->update(['fecha_inicio' => '2025-01-01']);

    $this->get(route('nomina.empleados.show', $this->empleado))
        ->assertInertia(fn (Assert $page) => $page
            ->where('saldoVacaciones.fechaIngreso', '2025-01-01')
            ->where('saldoVacaciones.diasCausados', '30.0000')
            ->where('saldoVacaciones.diasDisponibles', '30.0000'));
});

// Issue #188, criterios 2 y 4: la liquidación monetaria no duplica el consumo del saldo.
test('liquidar y reliquidar la nómina conserva el saldo de vacaciones', function () {
    $this->seed(NominaSeeder::class);
    $periodo = PeriodoNomina::factory()->create([
        'sede_id' => $this->sede->id,
        'fecha_inicio' => '2026-02-01',
        'fecha_fin' => '2026-02-28',
    ]);
    $novedad = Novedad::factory()->for($this->empleado)->create([
        'tipo' => TipoNovedad::Vacaciones,
        'fecha_inicio' => '2026-02-09',
        'fecha_fin' => '2026-02-13',
        'cantidad' => null,
    ]);
    $calcularSaldo = app(CalcularSaldoVacaciones::class);
    $saldoAntes = $calcularSaldo->ejecutar($this->empleado);

    app(LiquidarPeriodoNomina::class)->ejecutar($periodo, $this->superAdmin);
    $valoresPrimeraLiquidacion = ReciboNomina::vigentes()->sole()->only([
        'dias_liquidados', 'valor_total_devengado', 'valor_total_deducciones', 'valor_neto',
    ]);

    expect($calcularSaldo->ejecutar($this->empleado))->toBe($saldoAntes)
        ->and($novedad->fresh()->periodo_nomina_id)->toBe($periodo->id);

    app(LiquidarPeriodoNomina::class)->ejecutar($periodo->fresh(), $this->superAdmin);

    expect($calcularSaldo->ejecutar($this->empleado))->toBe($saldoAntes)
        ->and(Novedad::count())->toBe(1)
        ->and(ReciboNomina::vigentes()->count())->toBe(1)
        ->and(ReciboNomina::vigentes()->sole()->only(array_keys($valoresPrimeraLiquidacion)))->toBe($valoresPrimeraLiquidacion);

    $this->actingAs($this->superAdmin)
        ->get(route('nomina.empleados.show', $this->empleado))
        ->assertInertia(fn (Assert $page) => $page
            ->where('saldoVacaciones.diasUtilizados', 5)
            ->where('saldoVacaciones.diasDisponibles', '10.0000'));
});

// Issue #188, criterio 5: los roles de nómina consultan solo su propia sede.
test('el usuario de nómina consulta saldos de su sede y no evade el filtro cambiando el identificador', function (Rol $rol) {
    $usuario = User::factory()->enSede($this->sede, $rol)->create();
    $otraSede = Sede::factory()->create();
    $otroEmpleado = Empleado::factory()->create(['sede_id' => $otraSede->id]);
    Contrato::factory()->for($otroEmpleado)->create();

    $this->actingAs($usuario)
        ->get(route('nomina.empleados.show', $this->empleado))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('empleado.id', $this->empleado->id)
            ->where('saldoVacaciones.diasCausados', '15.0000'));

    $this->get(route('nomina.empleados.show', $otroEmpleado))->assertNotFound();

    $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Partial-Component' => 'Nomina/Empleados/Show',
        'X-Inertia-Partial-Data' => 'saldoVacaciones',
    ])->get(route('nomina.empleados.show', $otroEmpleado))->assertNotFound();
})->with([Rol::Administrador, Rol::AuxiliarNomina]);

// Issue #188, criterio 5: alcance global del Superadmin, incluidos empleados sin sede.
test('el super administrador consulta saldos de cualquier sede y de empleados sin sede', function () {
    $otraSede = Sede::factory()->create();
    $this->actingAs($this->superAdmin);

    foreach ([$this->sede->id, $otraSede->id, null] as $sedeId) {
        $empleado = Empleado::factory()->create(['sede_id' => $sedeId]);
        Contrato::factory()->for($empleado)->create(['fecha_inicio' => '2025-01-01']);

        $this->get(route('nomina.empleados.show', $empleado))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('empleado.id', $empleado->id)
                ->where('saldoVacaciones.diasCausados', '30.0000'));
    }
});

// Issue #188, criterio 5: la ausencia de sede nunca da acceso global a los demás roles.
test('los usuarios de nómina sin sede no pueden consultar saldos', function (Rol $rol) {
    $usuario = User::factory()->conRol($rol)->create(['sede_id' => null]);
    $sinSede = Empleado::factory()->create(['sede_id' => null]);

    $this->actingAs($usuario)
        ->get(route('nomina.empleados.show', $this->empleado))->assertNotFound();
    $this->get(route('nomina.empleados.show', $sinSede))->assertNotFound();
})->with([Rol::Administrador, Rol::AuxiliarNomina]);

// Issue #188, criterio 5: tampoco se exponen empleados sin sede al administrador de una sede.
test('el administrador de sede no consulta el saldo de un empleado sin sede', function () {
    $usuario = User::factory()->enSede($this->sede)->create();
    $sinSede = Empleado::factory()->create(['sede_id' => null]);

    $this->actingAs($usuario)
        ->get(route('nomina.empleados.show', $sinSede))->assertNotFound();
});

// Issue #188, criterio 5: el permiso de nómina se exige incluso dentro de la misma sede.
test('los roles sin permiso de nómina no consultan saldos de vacaciones', function (?Rol $rol) {
    $usuario = User::factory()->create(['rol' => $rol, 'sede_id' => $this->sede->id]);

    $this->actingAs($usuario)
        ->get(route('nomina.empleados.show', $this->empleado))->assertForbidden();
})->with([
    'contador' => Rol::Contador,
    'cajero' => Rol::Cajero,
    'sin rol' => null,
]);

// Issue #188, criterio 5: la consulta requiere una sesión autenticada.
test('un visitante debe iniciar sesión para consultar saldos de vacaciones', function () {
    $this->get(route('nomina.empleados.show', $this->empleado))->assertRedirect(route('login'));
});
