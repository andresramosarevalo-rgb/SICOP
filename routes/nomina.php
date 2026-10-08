<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:gestionar-nomina'])
    ->prefix('nomina')
    ->name('nomina.')
    ->group(function () {
        Route::inertia('/', 'Nomina/Inicio/Index')->name('inicio.index');
    });
