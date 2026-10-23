<?php

namespace Database\Factories;

use App\Models\Sede;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sede>
 */
class SedeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Sede '.fake()->unique()->city(),
            'ciudad' => fake()->city(),
            'direccion' => fake()->streetAddress(),
            'valor_base_caja' => 0,
            'es_activa' => true,
        ];
    }
}
