<?php

namespace Database\Factories;

use App\Models\AsignacionConcepto;
use App\Models\ConceptoNomina;
use App\Models\Empleado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AsignacionConcepto>
 */
class AsignacionConceptoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empleado_id' => Empleado::factory(),
            'concepto_nomina_id' => ConceptoNomina::factory(),
            'valor_asignado' => '100000.00',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => null,
        ];
    }
}
