<?php

use App\Enums\Rol;
use App\Models\ParametroNomina;
use App\Models\User;
use Database\Seeders\NominaSeeder;
use Inertia\Testing\AssertableInertia as Assert;

// Issue #9, criterio 1
test('la base de datos sembrada tiene los parámetros legales de 2026', function () {
    $this->seed(NominaSeeder::class);

    $parametros = ParametroNomina::where('anio', 2026)->sole();

    expect($parametros->valor_salario_minimo)->toBe('1750905.00')
        ->and($parametros->valor_auxilio_transporte)->toBe('249095.00')
        ->and($parametros->valor_uvt)->toBe('52374.00')
        ->and($parametros->porcentaje_salud_empleado)->toBe('4.00')
        ->and($parametros->porcentaje_pension_empleado)->toBe('4.00')
        ->and($parametros->horas_mensuales)->toBe(210)
        ->and($parametros->porcentaje_recargo_hora_extra_diurna)->toBe('25.00')
        ->and($parametros->porcentaje_recargo_hora_extra_nocturna)->toBe('75.00')
        ->and($parametros->porcentaje_recargo_nocturno)->toBe('35.00')
        ->and($parametros->porcentaje_recargo_dominical_festivo)->toBe('90.00')
        ->and($parametros->porcentaje_incapacidad)->toBe('66.67');
});

// Issue #9, criterio 1
test('volver a sembrar no duplica ni sobrescribe los parámetros ya ajustados', function () {
    ParametroNomina::factory()->create(['valor_uvt' => '53000.00']);

    $this->seed(NominaSeeder::class);

    expect(ParametroNomina::count())->toBe(1)
        ->and(ParametroNomina::sole()->valor_uvt)->toBe('53000.00');
});

// Issue #9, criterio 1
test('el listado de parámetros muestra los años registrados', function () {
    $this->seed(NominaSeeder::class);

    $this->actingAs(User::factory()->conRol(Rol::AuxiliarNomina)->create())
        ->get(route('nomina.parametros.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Nomina/Parametros/Index')
            ->where('parametros.0.anio', 2026));
});
