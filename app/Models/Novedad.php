<?php

namespace App\Models;

use App\Enums\TipoNovedad;
use Database\Factories\NovedadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Novedad de asistencia o de pago de un empleado: falta, retardo, horas extra, recargo,
 * incapacidad, vacaciones o un concepto eventual. Queda bloqueada cuando entra en una liquidación.
 *
 * @property int $id
 * @property int $empleado_id
 * @property TipoNovedad $tipo
 * @property Carbon $fecha_inicio
 * @property Carbon|null $fecha_fin
 * @property int|null $cantidad
 * @property int|null $concepto_nomina_id
 * @property string|null $valor_eventual
 * @property string|null $observacion
 * @property int|null $periodo_nomina_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['empleado_id', 'tipo', 'fecha_inicio', 'fecha_fin', 'cantidad', 'concepto_nomina_id', 'valor_eventual', 'observacion'])]
class Novedad extends Model
{
    /** @use HasFactory<NovedadFactory> */
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'novedades';

    /**
     * Empleado al que corresponde la novedad.
     *
     * @return BelongsTo<Empleado, $this>
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    /**
     * Concepto de un pago o descuento eventual.
     *
     * @return BelongsTo<ConceptoNomina, $this>
     */
    public function concepto(): BelongsTo
    {
        return $this->belongsTo(ConceptoNomina::class, 'concepto_nomina_id');
    }

    /**
     * Indica si la novedad ya entró en una liquidación y por lo tanto no se puede modificar.
     */
    public function estaLiquidada(): bool
    {
        return $this->periodo_nomina_id !== null;
    }

    /**
     * Días calendario que abarca la novedad, contando el primero y el último.
     */
    public function dias(): int
    {
        return (int) $this->fecha_inicio->diffInDays($this->fecha_fin ?? $this->fecha_inicio) + 1;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoNovedad::class,
            'fecha_inicio' => 'date:Y-m-d',
            'fecha_fin' => 'date:Y-m-d',
            'cantidad' => 'integer',
            'valor_eventual' => 'decimal:2',
        ];
    }
}
