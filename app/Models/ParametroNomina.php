<?php

namespace App\Models;

use Database\Factories\ParametroNominaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Valores legales de nómina vigentes en un año (SMMLV, auxilio de transporte, UVT, aportes y recargos).
 *
 * @property int $id
 * @property int $anio
 * @property numeric-string $valor_salario_minimo
 * @property numeric-string $valor_auxilio_transporte
 * @property numeric-string $valor_uvt
 * @property numeric-string $porcentaje_salud_empleado
 * @property numeric-string $porcentaje_pension_empleado
 * @property int $horas_mensuales
 * @property numeric-string $porcentaje_recargo_hora_extra_diurna
 * @property numeric-string $porcentaje_recargo_hora_extra_nocturna
 * @property numeric-string $porcentaje_recargo_nocturno
 * @property numeric-string $porcentaje_recargo_dominical_festivo
 * @property numeric-string $porcentaje_incapacidad
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'anio', 'valor_salario_minimo', 'valor_auxilio_transporte', 'valor_uvt',
    'porcentaje_salud_empleado', 'porcentaje_pension_empleado', 'horas_mensuales',
    'porcentaje_recargo_hora_extra_diurna', 'porcentaje_recargo_hora_extra_nocturna',
    'porcentaje_recargo_nocturno', 'porcentaje_recargo_dominical_festivo', 'porcentaje_incapacidad',
])]
class ParametroNomina extends Model
{
    /** @use HasFactory<ParametroNominaFactory> */
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'parametros_nomina';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'valor_salario_minimo' => 'decimal:2',
            'valor_auxilio_transporte' => 'decimal:2',
            'valor_uvt' => 'decimal:2',
            'porcentaje_salud_empleado' => 'decimal:2',
            'porcentaje_pension_empleado' => 'decimal:2',
            'horas_mensuales' => 'integer',
            'porcentaje_recargo_hora_extra_diurna' => 'decimal:2',
            'porcentaje_recargo_hora_extra_nocturna' => 'decimal:2',
            'porcentaje_recargo_nocturno' => 'decimal:2',
            'porcentaje_recargo_dominical_festivo' => 'decimal:2',
            'porcentaje_incapacidad' => 'decimal:2',
        ];
    }
}
