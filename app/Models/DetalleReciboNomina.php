<?php

namespace App\Models;

use App\Enums\TipoConcepto;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Línea de un recibo de nómina: un devengo o una deducción con su valor.
 *
 * @property int $id
 * @property int $recibo_nomina_id
 * @property int $concepto_nomina_id
 * @property TipoConcepto $tipo
 * @property string $descripcion
 * @property int|null $cantidad
 * @property numeric-string $valor_concepto
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['concepto_nomina_id', 'tipo', 'descripcion', 'cantidad', 'valor_concepto'])]
class DetalleReciboNomina extends Model
{
    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'detalles_recibo_nomina';

    /**
     * Concepto de la línea.
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
            'tipo' => TipoConcepto::class,
            'cantidad' => 'integer',
            'valor_concepto' => 'decimal:2',
        ];
    }
}
