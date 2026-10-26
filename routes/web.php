<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', fn (Request $request) => $request->user()->sede_id === null
        ? Inertia::render('Dashboard')
        : to_route('pos.inicio.index'))->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/nomina.php';
require __DIR__.'/pos.php';
