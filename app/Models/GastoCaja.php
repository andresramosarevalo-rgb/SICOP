<?php

namespace App\Models;

use Database\Factories\GastoCajaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Gasto pagado con el efectivo de la caja de una sede, con su soporte adjunto.
 *
 * @property int $id
 * @property int $cierre_caja_id
 * @property string $concepto
 * @property string $valor_gasto
 * @property string $ruta_soporte
 * @property string $nombre_soporte
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['cierre_caja_id', 'concepto', 'valor_gasto', 'ruta_soporte', 'nombre_soporte'])]
class GastoCaja extends Model
{
    /** @use HasFactory<GastoCajaFactory> */
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'gastos_caja';

    /**
     * Cierre de caja al que pertenece el gasto.
     *
     * @return BelongsTo<CierreCaja, $this>
     */
    public function cierreCaja(): BelongsTo
    {
        return $this->belongsTo(CierreCaja::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valor_gasto' => 'decimal:2',
        ];
    }
}
