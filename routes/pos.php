<?php

use App\Http\Controllers\Pos\CursoConsolidadoController;
use App\Http\Controllers\Pos\CursoController;
use App\Http\Controllers\Pos\InicioController;
use App\Http\Controllers\Pos\SedeAccesoController;
use App\Http\Controllers\Pos\SedeController;
use App\Http\Controllers\Pos\SoatController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->prefix('pos')
    ->name('pos.')
    ->group(function () {
        Route::middleware('can:usar-pos')->group(function () {
            Route::get('/', InicioController::class)->name('inicio.index');

            Route::resource('sedes.cursos', CursoController::class)->except(['show', 'destroy'])->shallow();
            Route::resource('sedes.soats', SoatController::class)->except(['show', 'destroy'])->shallow();
        });

        Route::middleware('can:gestionar-pos')->group(function () {
            Route::get('cursos', CursoConsolidadoController::class)->name('cursos.index');
            Route::resource('sedes', SedeController::class)->except(['show', 'destroy']);
            Route::put('sedes/{sede}/acceso', SedeAccesoController::class)->name('sedes.acceso.update');
        });
    });
