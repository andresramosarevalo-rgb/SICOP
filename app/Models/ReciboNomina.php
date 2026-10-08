<?php

namespace App\Models;

use Database\Factories\ReciboNominaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Recibo de pago de un empleado en un periodo, con sus totales. El desglose está en sus detalles.
 *
 * @property int $id
 * @property int $periodo_nomina_id
 * @property int $empleado_id
 * @property int $contrato_id
 * @property numeric-string $valor_salario_base
 * @property int $dias_liquidados
 * @property numeric-string $valor_total_devengado
 * @property numeric-string $valor_total_deducciones
 * @property numeric-string $valor_neto
 * @property Carbon|null $fecha_envio
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'empleado_id', 'contrato_id', 'valor_salario_base', 'dias_liquidados',
    'valor_total_devengado', 'valor_total_deducciones', 'valor_neto',
])]
class ReciboNomina extends Model
{
    /** @use HasFactory<ReciboNominaFactory> */
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'recibos_nomina';

    /**
     * Periodo al que pertenece el recibo.
     *
     * @return BelongsTo<PeriodoNomina, $this>
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoNomina::class, 'periodo_nomina_id');
    }

    /**
     * Empleado al que se le paga.
     *
     * @return BelongsTo<Empleado, $this>
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    /**
     * Devengos y deducciones del recibo.
     *
     * @return HasMany<DetalleReciboNomina, $this>
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleReciboNomina::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valor_salario_base' => 'decimal:2',
            'dias_liquidados' => 'integer',
            'valor_total_devengado' => 'decimal:2',
            'valor_total_deducciones' => 'decimal:2',
            'valor_neto' => 'decimal:2',
            'fecha_envio' => 'datetime',
        ];
    }
}
