<?php

use App\Http\Controllers\Nomina\AreaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:gestionar-nomina'])
    ->prefix('nomina')
    ->name('nomina.')
    ->group(function () {
        Route::inertia('/', 'Nomina/Inicio/Index')->name('inicio.index');

        Route::resource('areas', AreaController::class)->only(['index', 'store', 'update']);
    });
