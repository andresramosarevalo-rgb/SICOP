<?php

use App\Http\Controllers\Admin\AuditoriaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:ver-auditoria'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('auditoria', AuditoriaController::class)->name('auditoria.index');
    });
