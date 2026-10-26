<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\EstadoCierre;
use Carbon\CarbonInterface;
use Database\Factories\CierreCajaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Cierre de caja (arqueo) de una sede en un día. Mientras está abierto solo guarda los gastos;
 * al cerrarse se congelan las cifras del día.
 *
 * @property int $id
 * @property int $sede_id
 * @property Carbon $fecha
 * @property EstadoCierre $estado
 * @property string|null $valor_base_caja
 * @property string|null $valor_efectivo_ventas
 * @property string|null $valor_pago_gestores
 * @property string|null $valor_gastos
 * @property string|null $valor_efectivo_esperado
 * @property string|null $valor_efectivo_contado
 * @property string|null $valor_diferencia_efectivo
 * @property string|null $valor_ventas_datafono
 * @property string|null $valor_reportado_datafono
 * @property string|null $valor_diferencia_datafono
 * @property string|null $valor_ventas_qr
 * @property string|null $valor_reportado_qr
 * @property string|null $valor_diferencia_qr
 * @property string|null $valor_a_consignar
 * @property string|null $justificacion
 * @property int|null $cerrado_por
 * @property Carbon|null $cerrado_en
 * @property int|null $reabierto_por
 * @property Carbon|null $reabierto_en
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'sede_id', 'fecha', 'estado', 'valor_base_caja', 'valor_efectivo_ventas', 'valor_pago_gestores',
    'valor_gastos', 'valor_efectivo_esperado', 'valor_efectivo_contado', 'valor_diferencia_efectivo',
    'valor_ventas_datafono', 'valor_reportado_datafono', 'valor_diferencia_datafono', 'valor_ventas_qr',
    'valor_reportado_qr', 'valor_diferencia_qr', 'valor_a_consignar', 'justificacion', 'cerrado_por',
    'cerrado_en', 'reabierto_por', 'reabierto_en',
])]
class CierreCaja extends Model
{
    /** @use HasFactory<CierreCajaFactory> */
    use Auditable, HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'cierres_caja';

    /**
     * Indica si el día ya fue cerrado y sus ventas y gastos no se pueden modificar.
     */
    public function estaCerrado(): bool
    {
        return $this->estado === EstadoCierre::Cerrado;
    }

    /**
     * Indica si la sede ya cerró la caja de un día: desde entonces no se registran ni modifican
     * sus cursos, SOAT ni gastos.
     */
    public static function diaCerrado(int $sedeId, CarbonInterface|string $fecha): bool
    {
        return static::query()
            ->where('sede_id', $sedeId)
            ->whereDate('fecha', $fecha instanceof CarbonInterface ? $fecha->toDateString() : $fecha)
            ->where('estado', EstadoCierre::Cerrado)
            ->exists();
    }

    /**
     * Sede a la que pertenece el cierre.
     *
     * @return BelongsTo<Sede, $this>
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    /**
     * Usuario que cerró el día.
     *
     * @return BelongsTo<User, $this>
     */
    public function cerradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }

    /**
     * Consignación al banco del efectivo de este día, si ya se hizo.
     *
     * @return HasOne<Consignacion, $this>
     */
    public function consignacion(): HasOne
    {
        return $this->hasOne(Consignacion::class);
    }

    /**
     * Gastos pagados con el efectivo de la caja ese día.
     *
     * @return HasMany<GastoCaja, $this>
     */
    public function gastos(): HasMany
    {
        return $this->hasMany(GastoCaja::class);
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
            'etiqueta' => 'Cierre de caja',
            'descripcion' => "Cierre del {$this->fecha->toDateString()}",
            'sede_id' => $this->sede_id,
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        $decimales = array_fill_keys([
            'valor_base_caja', 'valor_efectivo_ventas', 'valor_pago_gestores', 'valor_gastos',
            'valor_efectivo_esperado', 'valor_efectivo_contado', 'valor_diferencia_efectivo',
            'valor_ventas_datafono', 'valor_reportado_datafono', 'valor_diferencia_datafono',
            'valor_ventas_qr', 'valor_reportado_qr', 'valor_diferencia_qr', 'valor_a_consignar',
        ], 'decimal:2');

        return [
            ...$decimales,
            'fecha' => 'date:Y-m-d',
            'estado' => EstadoCierre::class,
            'cerrado_en' => 'datetime',
            'reabierto_en' => 'datetime',
        ];
    }
}
