<?php

use App\Models\Novedad;

// Issue #12, criterio 2
test('una novedad por días cuenta el primer y el último día', function (?string $fechaFin, int $dias) {
    $novedad = new Novedad(['fecha_inicio' => '2026-02-10', 'fecha_fin' => $fechaFin]);

    expect($novedad->dias())->toBe($dias);
})->with([
    'un solo día' => [null, 1],
    'tres días' => ['2026-02-12', 3],
    'cruza de mes' => ['2026-03-01', 20],
]);

// Issue #12, criterio 4
test('una novedad está liquidada cuando pertenece a un periodo de nómina', function () {
    $novedad = new Novedad;
    expect($novedad->estaLiquidada())->toBeFalse();

    $novedad->periodo_nomina_id = 5;
    expect($novedad->estaLiquidada())->toBeTrue();
});
