<?php

use App\Http\Controllers\Pos\CursoController;
use App\Http\Controllers\Pos\SedeController;
use App\Http\Controllers\Pos\SoatController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:gestionar-pos'])
    ->prefix('pos')
    ->name('pos.')
    ->group(function () {
        Route::inertia('/', 'Pos/Inicio/Index')->name('inicio.index');

        Route::resource('sedes', SedeController::class)->except(['show', 'destroy']);
        Route::resource('sedes.cursos', CursoController::class)->except(['show', 'destroy'])->shallow();
        Route::resource('sedes.soats', SoatController::class)->only(['index']);
    });
