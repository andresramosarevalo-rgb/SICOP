<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\ConsignacionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Consignación al banco del efectivo de un día cerrado: una por sede y por día.
 *
 * @property int $id
 * @property int $cierre_caja_id
 * @property Carbon $fecha_consignacion
 * @property string $valor_consignado
 * @property string $referencia
 * @property string $ruta_soporte
 * @property string $nombre_soporte
 * @property int $registrado_por
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['cierre_caja_id', 'fecha_consignacion', 'valor_consignado', 'referencia', 'ruta_soporte', 'nombre_soporte', 'registrado_por'])]
class Consignacion extends Model
{
    /** @use HasFactory<ConsignacionFactory> */
    use Auditable, HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'consignaciones';

    /**
     * Cierre de caja cuyo efectivo se consignó.
     *
     * @return BelongsTo<CierreCaja, $this>
     */
    public function cierreCaja(): BelongsTo
    {
        return $this->belongsTo(CierreCaja::class);
    }

    /**
     * Cómo se presenta el registro en el historial de auditoría.
     *
     * @return array{modulo: string, etiqueta: string, descripcion: string, sede_id?: int|null}
     */
    public function configuracionAuditoria(): array
    {
        return [
            'modulo' => 'pos',
            'etiqueta' => 'Consignación',
            'descripcion' => "Referencia {$this->referencia}",
            'sede_id' => $this->cierreCaja?->sede_id,
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
            'fecha_consignacion' => 'date:Y-m-d',
            'valor_consignado' => 'decimal:2',
        ];
    }
}
