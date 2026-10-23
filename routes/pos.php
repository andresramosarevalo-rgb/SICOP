<?php

use App\Http\Controllers\Pos\CierreCajaController;
use App\Http\Controllers\Pos\CierreCajaGeneralController;
use App\Http\Controllers\Pos\ConsignacionController;
use App\Http\Controllers\Pos\CursoConsolidadoController;
use App\Http\Controllers\Pos\CursoController;
use App\Http\Controllers\Pos\ExportarCursosController;
use App\Http\Controllers\Pos\ExportarSoatsController;
use App\Http\Controllers\Pos\GastoCajaController;
use App\Http\Controllers\Pos\InicioController;
use App\Http\Controllers\Pos\SaldoConsignacionController;
use App\Http\Controllers\Pos\SedeAccesoController;
use App\Http\Controllers\Pos\SedeController;
use App\Http\Controllers\Pos\SoatConsolidadoController;
use App\Http\Controllers\Pos\SoatController;
use App\Http\Controllers\Pos\TarifaGestorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->prefix('pos')
    ->name('pos.')
    ->group(function () {
        Route::middleware('can:usar-pos')->group(function () {
            Route::get('/', InicioController::class)->name('inicio.index');

            Route::resource('sedes.cursos', CursoController::class)->except(['show', 'destroy'])->shallow();
            Route::resource('sedes.soats', SoatController::class)->except(['show', 'destroy'])->shallow();

            Route::get('sedes/{sede}/cierre', [CierreCajaController::class, 'show'])->name('sedes.cierre.show');
            Route::post('sedes/{sede}/cierre', [CierreCajaController::class, 'store'])->name('sedes.cierre.store');
            Route::post('sedes/{sede}/cierre/gastos', [GastoCajaController::class, 'store'])->name('sedes.cierre.gastos.store');
            Route::delete('cierre/gastos/{gasto}', [GastoCajaController::class, 'destroy'])->name('cierre.gastos.destroy');
            Route::get('consignaciones/{consignacion}/soporte', [ConsignacionController::class, 'soporte'])->name('consignaciones.soporte');
            Route::get('cierre/gastos/{gasto}/soporte', [GastoCajaController::class, 'soporte'])->name('cierre.gastos.soporte');
        });

        Route::middleware('can:gestionar-pos')->group(function () {
            Route::resource('sedes', SedeController::class)->except(['show', 'destroy']);
            Route::resource('tarifas', TarifaGestorController::class)->only(['index', 'store', 'destroy']);
            Route::get('cursos', CursoConsolidadoController::class)->name('cursos.index');
            Route::get('cursos/exportar', ExportarCursosController::class)->name('cursos.exportar');
            Route::get('soats/exportar', ExportarSoatsController::class)->name('soats.exportar');
            Route::get('soats', SoatConsolidadoController::class)->name('soats.index');
            Route::get('cierres', CierreCajaGeneralController::class)->name('cierres.index');
            Route::get('consignaciones', SaldoConsignacionController::class)->name('consignaciones.index');
            Route::post('cierre/{cierre}/consignacion', [ConsignacionController::class, 'store'])->name('cierre.consignacion.store');
            Route::delete('consignaciones/{consignacion}', [ConsignacionController::class, 'destroy'])->name('consignaciones.destroy');
            Route::post('sedes/{sede}/cierre/reabrir', [CierreCajaController::class, 'reabrir'])->name('sedes.cierre.reabrir');
            Route::put('sedes/{sede}/acceso', SedeAccesoController::class)->name('sedes.acceso.update');
        });
    });
