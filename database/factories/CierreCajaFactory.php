<?php

namespace Database\Factories;

use App\Enums\EstadoCierre;
use App\Models\CierreCaja;
use App\Models\Sede;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CierreCaja>
 */
class CierreCajaFactory extends Factory
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
            'fecha' => today(),
            'estado' => EstadoCierre::Abierto,
        ];
    }

    /**
     * Cierre de un día ya cerrado.
     */
    public function cerrado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoCierre::Cerrado,
            'cerrado_en' => now(),
        ]);
    }
}
