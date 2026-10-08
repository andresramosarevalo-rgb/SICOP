<?php

namespace Database\Factories;

use App\Enums\FormaCalculo;
use App\Enums\TipoConcepto;
use App\Models\ConceptoNomina;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConceptoNomina>
 */
class ConceptoNominaFactory extends Factory
{
    /**
     * Define the model's default state: un bono de valor fijo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->regexify('BONO_[A-Z]{5}'),
            'nombre' => 'Bono '.fake()->word(),
            'tipo' => TipoConcepto::Devengo,
            'forma_calculo' => FormaCalculo::ValorFijo,
            'valor_base' => '100000.00',
            'porcentaje_base' => null,
            'es_constitutivo_salario' => false,
            'es_sistema' => false,
            'es_activo' => true,
        ];
    }
}
