<?php

namespace App\Models;

use App\Enums\CodigoInfraccion;
use Database\Factories\TarifaGestorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Valor que se paga al gestor por un curso según el código de infracción. Sin sede es la tarifa
 * base; con sede es el ajuste de esa sede.
 *
 * @property int $id
 * @property CodigoInfraccion $codigo_infraccion
 * @property int|null $sede_id
 * @property string $valor_pago_gestor
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['codigo_infraccion', 'sede_id', 'valor_pago_gestor'])]
class TarifaGestor extends Model
{
    /** @use HasFactory<TarifaGestorFactory> */
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'tarifas_gestor';

    /**
     * Sede a la que aplica el ajuste; nulo en la tarifa base.
     *
     * @return BelongsTo<Sede, $this>
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'codigo_infraccion' => CodigoInfraccion::class,
            'valor_pago_gestor' => 'decimal:2',
        ];
    }
}
