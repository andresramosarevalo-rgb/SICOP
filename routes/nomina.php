<?php

use App\Http\Controllers\Nomina\AreaController;
use App\Http\Controllers\Nomina\AsignacionConceptoController;
use App\Http\Controllers\Nomina\ConceptoNominaController;
use App\Http\Controllers\Nomina\ContratoController;
use App\Http\Controllers\Nomina\EmpleadoController;
use App\Http\Controllers\Nomina\NovedadController;
use App\Http\Controllers\Nomina\ParametroNominaController;
use App\Http\Controllers\Nomina\PeriodoNominaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:gestionar-nomina'])
    ->prefix('nomina')
    ->name('nomina.')
    ->group(function () {
        Route::inertia('/', 'Nomina/Inicio/Index')->name('inicio.index');

        Route::resource('areas', AreaController::class)->only(['index', 'store', 'update']);
        Route::resource('empleados', EmpleadoController::class)->except(['destroy']);
        Route::patch('empleados/{empleado}/estado', [EmpleadoController::class, 'cambiarEstado'])->name('empleados.estado');
        Route::resource('empleados.contratos', ContratoController::class)->only(['create', 'store']);
        Route::resource('empleados.asignaciones', AsignacionConceptoController::class)
            ->only(['store', 'destroy'])->shallow()->parameters(['asignaciones' => 'asignacion']);
        Route::resource('novedades', NovedadController::class)->except(['show'])->parameters(['novedades' => 'novedad']);
        Route::resource('parametros', ParametroNominaController::class)->except(['show', 'destroy']);
        Route::resource('conceptos', ConceptoNominaController::class)->except(['show']);
        Route::resource('periodos', PeriodoNominaController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('periodos/{periodo}/liquidar', [PeriodoNominaController::class, 'liquidar'])->name('periodos.liquidar');
    });
