<?php

namespace Database\Factories;

use App\Enums\TipoDocumento;
use App\Models\Sede;
use App\Models\Soat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Soat>
 */
class SoatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sede_id' => Sede::factory(),
            'fecha' => fake()->dateTimeBetween('-30 days', 'today'),
            'tipo_documento' => TipoDocumento::CedulaCiudadania,
            'numero_documento' => fake()->numerify('##########'),
            'nombre_cliente' => fake()->name(),
            'valor_soat' => fake()->numberBetween(300, 1500) * 1000,
        ];
    }
}
