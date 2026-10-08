<?php

namespace Database\Seeders;

use App\Models\ParametroNomina;
use Illuminate\Database\Seeder;

class NominaSeeder extends Seeder
{
    /**
     * Valores legales de referencia para 2026. Pendientes de confirmación del contador de
     * COVIACOL (issue #9); se pueden ajustar desde la pantalla de parámetros.
     *
     * @var array<string, int|string>
     */
    public const PARAMETROS_2026 = [
        'anio' => 2026,
        'valor_salario_minimo' => '1750905.00',
        'valor_auxilio_transporte' => '249095.00',
        'valor_uvt' => '52374.00',
        'porcentaje_salud_empleado' => '4.00',
        'porcentaje_pension_empleado' => '4.00',
        'horas_mensuales' => 210,
        'porcentaje_recargo_hora_extra_diurna' => '25.00',
        'porcentaje_recargo_hora_extra_nocturna' => '75.00',
        'porcentaje_recargo_nocturno' => '35.00',
        'porcentaje_recargo_dominical_festivo' => '90.00',
        'porcentaje_incapacidad' => '66.67',
    ];

    /**
     * Carga los parámetros legales de nómina. Si el año ya existe, no lo modifica.
     */
    public function run(): void
    {
        ParametroNomina::firstOrCreate(['anio' => 2026], self::PARAMETROS_2026);
    }
}
