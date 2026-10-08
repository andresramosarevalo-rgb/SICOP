<?php

namespace Database\Factories;

use App\Enums\TipoDocumento;
use App\Models\Area;
use App\Models\Empleado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Empleado>
 */
class EmpleadoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo_documento' => TipoDocumento::CedulaCiudadania,
            'numero_documento' => fake()->unique()->numerify('##########'),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'telefono' => fake()->numerify('3#########'),
            'direccion' => fake()->streetAddress(),
            'fecha_nacimiento' => fake()->dateTimeBetween('-60 years', '-18 years'),
            'area_id' => Area::factory(),
            'cargo' => fake()->jobTitle(),
            'es_activo' => true,
        ];
    }
}
