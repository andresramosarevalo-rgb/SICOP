<?php

namespace Database\Factories;

use App\Enums\TipoDocumento;
use App\Models\Curso;
use App\Models\Sede;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Curso>
 */
class CursoFactory extends Factory
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
            'fecha' => fake()->dateTimeBetween('-30 days', 'today'),
            'tipo_documento' => TipoDocumento::CedulaCiudadania,
            'numero_documento' => fake()->numerify('##########'),
            'nombre_cliente' => fake()->name(),
            'celular' => fake()->numerify('3#########'),
            'correo' => fake()->safeEmail(),
            'numero_certificado' => fake()->numerify('CERT-######'),
            'numero_comparendo' => fake()->numerify('####################'),
            'secretaria_transito' => 'Secretaría de Tránsito de '.fake()->city(),
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
            'referido' => null,
            'tipo_comparendo' => fake()->randomElement(['Tipo A', 'Tipo B', 'Tipo C']),
            'valor_curso' => fake()->numberBetween(200, 700) * 1000,
            'porcentaje_descuento' => fake()->randomElement(Curso::PORCENTAJES_DESCUENTO),
        ];
    }
}
