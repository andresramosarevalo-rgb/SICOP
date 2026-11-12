<?php

use App\Enums\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => to_route('login'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', fn (Request $request) => $request->user()->rol === Rol::Cajero
        ? to_route('pos.inicio.index')
        : Inertia::render('Dashboard'))->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/nomina.php';
require __DIR__.'/pos.php';
require __DIR__.'/admin.php';
