<?php

use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:ver-auditoria'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('auditoria', AuditoriaController::class)->name('auditoria.index');
    });

Route::middleware(['auth', 'verified', 'can:gestionar-usuarios'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('usuarios', UsuarioController::class)->only(['index', 'create', 'store']);
    });
