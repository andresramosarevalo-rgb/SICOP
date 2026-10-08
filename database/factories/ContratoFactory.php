<?php

namespace Database\Factories;

use App\Enums\PeriodicidadPago;
use App\Enums\TipoContrato;
use App\Models\Contrato;
use App\Models\Empleado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contrato>
 */
class ContratoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empleado_id' => Empleado::factory(),
            'tipo_contrato' => TipoContrato::TerminoIndefinido,
            'periodicidad_pago' => PeriodicidadPago::Mensual,
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => null,
            'valor_salario_base' => '1750905.00',
            'es_vigente' => true,
        ];
    }
}
