<?php

namespace App\Models;

use Database\Factories\AsignacionConceptoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Concepto recurrente asignado a un empleado (bono fijo, libranza…) que se aplica en cada
 * liquidación mientras esté vigente. Si `valor_asignado` es nulo se usa el valor del concepto.
 *
 * @property int $id
 * @property int $empleado_id
 * @property int $concepto_nomina_id
 * @property numeric-string|null $valor_asignado
 * @property Carbon $fecha_inicio
 * @property Carbon|null $fecha_fin
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['concepto_nomina_id', 'valor_asignado', 'fecha_inicio', 'fecha_fin'])]
class AsignacionConcepto extends Model
{
    /** @use HasFactory<AsignacionConceptoFactory> */
    use HasFactory;

    /**
     * La tabla asociada al modelo (pivote entre conceptos de nómina y empleados).
     *
     * @var string
     */
    protected $table = 'concepto_nomina_empleado';

    /**
     * Empleado al que se asignó el concepto.
     *
     * @return BelongsTo<Empleado, $this>
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    /**
     * Concepto asignado.
     *
     * @return BelongsTo<ConceptoNomina, $this>
     */
    public function concepto(): BelongsTo
    {
        return $this->belongsTo(ConceptoNomina::class, 'concepto_nomina_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valor_asignado' => 'decimal:2',
            'fecha_inicio' => 'date:Y-m-d',
            'fecha_fin' => 'date:Y-m-d',
        ];
    }
}
