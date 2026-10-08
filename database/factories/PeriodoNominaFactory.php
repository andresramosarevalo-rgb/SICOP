<?php

namespace Database\Factories;

use App\Enums\EstadoPeriodo;
use App\Enums\PeriodicidadPago;
use App\Models\PeriodoNomina;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeriodoNomina>
 */
class PeriodoNominaFactory extends Factory
{
    /**
     * Define the model's default state: el mes de febrero de 2026.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'periodicidad_pago' => PeriodicidadPago::Mensual,
            'fecha_inicio' => '2026-02-01',
            'fecha_fin' => '2026-02-28',
            'estado' => EstadoPeriodo::Borrador,
        ];
    }

    /**
     * Indica que el periodo está cerrado.
     */
    public function cerrado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoPeriodo::Cerrado,
            'fecha_cierre' => now(),
        ]);
    }
}
