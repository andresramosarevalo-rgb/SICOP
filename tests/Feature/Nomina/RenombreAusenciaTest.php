<?php

use App\Enums\TipoNovedad;
use App\Models\Empleado;
use App\Models\Novedad;
use Illuminate\Support\Facades\DB;

// Issue #23, criterio 1
test('la novedad de días no trabajados se muestra como ausencia', function () {
    expect(TipoNovedad::Ausencia->etiqueta())->toBe('Ausencia')
        ->and(TipoNovedad::tryFrom('falta'))->toBeNull();
});

// Issue #23, criterio 3
test('la migración convierte las novedades guardadas como falta en ausencia', function () {
    $empleado = Empleado::factory()->create();
    DB::table('novedades')->insert([
        'empleado_id' => $empleado->id, 'tipo' => 'falta', 'fecha_inicio' => '2026-02-16',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    (require database_path('migrations/2026_10_11_153151_renombrar_falta_a_ausencia_en_novedades.php'))->up();

    expect(Novedad::sole()->tipo)->toBe(TipoNovedad::Ausencia);
});
