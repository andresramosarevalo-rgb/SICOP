<?php

namespace Database\Factories;

use App\Models\CierreCaja;
use App\Models\Consignacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consignacion>
 */
class ConsignacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cierre_caja_id' => CierreCaja::factory()->cerrado(),
            'fecha_consignacion' => today(),
            'valor_consignado' => 100000,
            'referencia' => fake()->numerify('REF-######'),
            'ruta_soporte' => 'soportes-consignacion/'.fake()->uuid().'.pdf',
            'nombre_soporte' => 'comprobante.pdf',
            'registrado_por' => User::factory(),
        ];
    }
}
