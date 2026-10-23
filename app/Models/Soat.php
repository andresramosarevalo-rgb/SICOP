<?php

namespace App\Models;

use App\Enums\TipoDocumento;
use Database\Factories\SoatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * SOAT vendido en una sede.
 *
 * @property int $id
 * @property int $sede_id
 * @property TipoDocumento $tipo_documento
 * @property string $numero_documento
 * @property string $nombre_cliente
 * @property Carbon $fecha
 * @property string $valor_soat
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['sede_id', 'fecha', 'tipo_documento', 'numero_documento', 'nombre_cliente', 'valor_soat'])]
class Soat extends Model
{
    /** @use HasFactory<SoatFactory> */
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'soats';

    /**
     * Sede donde se vendió el SOAT.
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
            'fecha' => 'date:Y-m-d',
            'tipo_documento' => TipoDocumento::class,
            'valor_soat' => 'decimal:2',
        ];
    }
}
