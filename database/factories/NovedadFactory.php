<?php

namespace Database\Factories;

use App\Enums\TipoNovedad;
use App\Models\Empleado;
use App\Models\Novedad;
use App\Models\PeriodoNomina;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Novedad>
 */
class NovedadFactory extends Factory
{
    /**
     * Define the model's default state: dos horas extra diurnas.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empleado_id' => Empleado::factory(),
            'tipo' => TipoNovedad::HoraExtraDiurna,
            'fecha_inicio' => '2026-02-10',
            'fecha_fin' => null,
            'cantidad' => 2,
            'concepto_nomina_id' => null,
            'valor_eventual' => null,
            'observacion' => null,
        ];
    }

    /**
     * Indica que la novedad ya entró en la liquidación de un periodo.
     */
    public function liquidada(?PeriodoNomina $periodo = null): static
    {
        return $this->state(fn (array $attributes) => [
            'periodo_nomina_id' => $periodo ?? PeriodoNomina::factory(),
        ]);
    }
}
