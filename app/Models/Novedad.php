<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\FiltraPorSedeDelEmpleado;
use App\Enums\TipoNovedad;
use Database\Factories\NovedadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Novedad de asistencia o de pago de un empleado: ausencia, retardo, horas extra, recargo,
 * incapacidad, vacaciones o un concepto eventual. Queda bloqueada cuando entra en una liquidación.
 *
 * @property int $id
 * @property int $empleado_id
 * @property TipoNovedad $tipo
 * @property Carbon $fecha_inicio
 * @property Carbon|null $fecha_fin
 * @property int|null $cantidad
 * @property int|null $concepto_nomina_id
 * @property numeric-string|null $valor_eventual
 * @property string|null $observacion
 * @property int|null $periodo_nomina_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['empleado_id', 'tipo', 'fecha_inicio', 'fecha_fin', 'cantidad', 'concepto_nomina_id', 'valor_eventual', 'observacion'])]
class Novedad extends Model
{
    /** @use HasFactory<NovedadFactory> */
    use Auditable, FiltraPorSedeDelEmpleado, HasFactory;

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
     * Periodo de nómina en cuya liquidación entró la novedad; sin periodo, aún no se ha liquidado.
     *
     * @return BelongsTo<PeriodoNomina, $this>
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoNomina::class, 'periodo_nomina_id');
    }

    /**
     * Indica si la novedad ya entró en una liquidación.
     */
    public function estaLiquidada(): bool
    {
        return $this->periodo_nomina_id !== null;
    }

    /**
     * Indica si la novedad no se puede modificar: entró en una liquidación cuyo periodo no está reabierto.
     * Las de un periodo reabierto se pueden corregir; las de cualquier otro periodo siguen protegidas.
     */
    public function estaBloqueada(): bool
    {
        return $this->estaLiquidada() && ! $this->periodo?->estaReabierto();
    }

    /**
     * Días calendario que abarca la novedad, contando el primero y el último.
     */
    public function dias(): int
    {
        return (int) $this->fecha_inicio->diffInDays($this->fecha_fin ?? $this->fecha_inicio) + 1;
    }

    /**
     * Cómo se presenta el registro en el historial de auditoría.
     *
     * @return array{modulo: string, etiqueta: string, descripcion: string, sede_id?: int|null}
     */
    public function configuracionAuditoria(): array
    {
        return [
            'modulo' => 'nomina',
            'etiqueta' => 'Novedad',
            'descripcion' => "{$this->tipo->value} de {$this->empleado?->nombres} {$this->empleado?->apellidos}",
            'sede_id' => $this->empleado?->sede_id,
        ];
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
