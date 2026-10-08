<?php

namespace App\Models;

use App\Enums\PeriodicidadPago;
use App\Enums\TipoContrato;
use Database\Factories\ContratoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Contrato laboral de un empleado. Solo uno por empleado puede estar vigente.
 *
 * @property int $id
 * @property int $empleado_id
 * @property TipoContrato $tipo_contrato
 * @property PeriodicidadPago $periodicidad_pago
 * @property Carbon $fecha_inicio
 * @property Carbon|null $fecha_fin
 * @property string $valor_salario_base
 * @property bool $es_vigente
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tipo_contrato', 'periodicidad_pago', 'fecha_inicio', 'fecha_fin', 'valor_salario_base', 'es_vigente'])]
class Contrato extends Model
{
    /** @use HasFactory<ContratoFactory> */
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'contratos';

    /**
     * Empleado al que pertenece el contrato.
     *
     * @return BelongsTo<Empleado, $this>
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_contrato' => TipoContrato::class,
            'periodicidad_pago' => PeriodicidadPago::class,
            'fecha_inicio' => 'date:Y-m-d',
            'fecha_fin' => 'date:Y-m-d',
            'valor_salario_base' => 'decimal:2',
            'es_vigente' => 'boolean',
        ];
    }
}
