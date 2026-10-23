<?php

namespace Database\Factories;

use App\Models\CierreCaja;
use App\Models\GastoCaja;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GastoCaja>
 */
class GastoCajaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cierre_caja_id' => CierreCaja::factory(),
            'concepto' => fake()->sentence(3),
            'valor_gasto' => fake()->numberBetween(5, 50) * 1000,
            'ruta_soporte' => 'soportes-caja/'.fake()->uuid().'.pdf',
            'nombre_soporte' => 'soporte.pdf',
        ];
    }
}
