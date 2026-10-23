<?php

namespace Database\Factories;

use App\Enums\CodigoInfraccion;
use App\Models\Sede;
use App\Models\TarifaGestor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TarifaGestor>
 */
class TarifaGestorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo_infraccion' => CodigoInfraccion::C,
            'sede_id' => null,
            'valor_pago_gestor' => 20000,
        ];
    }

    /**
     * Ajuste de la tarifa para una sede.
     */
    public function paraSede(Sede $sede): static
    {
        return $this->state(fn (array $attributes) => ['sede_id' => $sede->id]);
    }
}
