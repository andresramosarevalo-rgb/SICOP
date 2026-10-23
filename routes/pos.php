<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:gestionar-pos'])
    ->prefix('pos')
    ->name('pos.')
    ->group(function () {
        Route::inertia('/', 'Pos/Inicio/Index')->name('inicio.index');
    });
