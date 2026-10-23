<?php

namespace Database\Factories;

use App\Models\Auditoria;
use App\Models\Curso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Auditoria>
 */
class AuditoriaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'modulo' => 'pos',
            'evento' => 'modificado',
            'auditable_type' => Curso::class,
            'auditable_id' => fake()->numberBetween(1, 1000),
            'etiqueta' => 'Curso',
            'descripcion' => fake()->name(),
            'sede_id' => null,
            'cambios' => ['valor_curso' => ['antes' => '350000.00', 'despues' => '300000.00']],
        ];
    }
}
