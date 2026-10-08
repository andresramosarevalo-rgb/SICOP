<?php

namespace Database\Factories;

use App\Models\ParametroNomina;
use Database\Seeders\NominaSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParametroNomina>
 */
class ParametroNominaFactory extends Factory
{
    /**
     * Define the model's default state: los valores de referencia de 2026.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return NominaSeeder::PARAMETROS_2026;
    }
}
