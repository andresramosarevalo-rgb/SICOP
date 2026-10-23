<?php

namespace App\Models;

use App\Enums\CodigoInfraccion;
use App\Enums\FormaPago;
use App\Enums\Referido;
use App\Enums\TipoDocumento;
use Database\Factories\CursoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Curso pedagógico por infracción de tránsito dictado en una sede.
 *
 * @property int $id
 * @property int $sede_id
 * @property TipoDocumento $tipo_documento
 * @property string $numero_documento
 * @property string $nombre_cliente
 * @property string $celular
 * @property string $correo
 * @property string $numero_certificado
 * @property string $numero_comparendo
 * @property string $secretaria_transito
 * @property string $hora_inicio
 * @property string $hora_fin
 * @property Referido|null $referido
 * @property Carbon $fecha
 * @property CodigoInfraccion $codigo_infraccion
 * @property string $valor_curso
 * @property string $porcentaje_descuento
 * @property string $valor_pago_gestor
 * @property FormaPago $forma_pago
 * @property string $valor_efectivo
 * @property string $valor_datafono
 * @property string $valor_qr
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'sede_id', 'fecha', 'tipo_documento', 'numero_documento', 'nombre_cliente', 'celular', 'correo',
    'numero_certificado', 'numero_comparendo', 'secretaria_transito', 'hora_inicio', 'hora_fin',
    'referido', 'codigo_infraccion', 'valor_curso', 'porcentaje_descuento', 'valor_pago_gestor', 'forma_pago',
    'valor_efectivo', 'valor_datafono', 'valor_qr',
])]
class Curso extends Model
{
    /** @use HasFactory<CursoFactory> */
    use HasFactory;

    /**
     * Porcentajes de descuento que la ley permite sobre el comparendo.
     *
     * @var list<int>
     */
    public const PORCENTAJES_DESCUENTO = [25, 50];

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'cursos';

    /**
     * Sede donde se dictó el curso.
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
            'valor_curso' => 'decimal:2',
            'porcentaje_descuento' => 'decimal:2',
            'referido' => Referido::class,
            'codigo_infraccion' => CodigoInfraccion::class,
            'valor_pago_gestor' => 'decimal:2',
            'forma_pago' => FormaPago::class,
            'valor_efectivo' => 'decimal:2',
            'valor_datafono' => 'decimal:2',
            'valor_qr' => 'decimal:2',
        ];
    }
}
