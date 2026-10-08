<?php

namespace Database\Factories;

use App\Models\Contrato;
use App\Models\PeriodoNomina;
use App\Models\ReciboNomina;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReciboNomina>
 */
class ReciboNominaFactory extends Factory
{
    /**
     * Define the model's default state: el recibo mensual de un salario mínimo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'periodo_nomina_id' => PeriodoNomina::factory(),
            'contrato_id' => Contrato::factory(),
            'empleado_id' => fn (array $attributes) => Contrato::query()->whereKey($attributes['contrato_id'])->value('empleado_id'),
            'valor_salario_base' => '1750905.00',
            'dias_liquidados' => 30,
            'valor_total_devengado' => '2000000.00',
            'valor_total_deducciones' => '140072.00',
            'valor_neto' => '1859928.00',
        ];
    }
}
