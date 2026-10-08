<?php

use App\Http\Controllers\Nomina\AreaController;
use App\Http\Controllers\Nomina\ContratoController;
use App\Http\Controllers\Nomina\EmpleadoController;
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
    });
