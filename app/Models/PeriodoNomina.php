<?php

namespace App\Models;

use App\Enums\EstadoPeriodo;
use App\Enums\PeriodicidadPago;
use Database\Factories\PeriodoNominaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Periodo de pago (semana, quincena o mes) que se liquida para los empleados con esa periodicidad.
 *
 * @property int $id
 * @property PeriodicidadPago $periodicidad_pago
 * @property Carbon $fecha_inicio
 * @property Carbon $fecha_fin
 * @property EstadoPeriodo $estado
 * @property int|null $liquidado_por
 * @property Carbon|null $fecha_liquidacion
 * @property Carbon|null $fecha_cierre
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['periodicidad_pago', 'fecha_inicio', 'fecha_fin'])]
class PeriodoNomina extends Model
{
    /** @use HasFactory<PeriodoNominaFactory> */
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'periodos_nomina';

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'estado' => 'borrador',
    ];

    /**
     * Novedades que entraron en la liquidación del periodo.
     *
     * @return HasMany<Novedad, $this>
     */
    public function novedades(): HasMany
    {
        return $this->hasMany(Novedad::class);
    }

    /**
     * Usuario que hizo la última liquidación.
     *
     * @return BelongsTo<User, $this>
     */
    public function liquidador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'liquidado_por');
    }

    /**
     * Indica si el periodo está cerrado y por lo tanto ya no se puede volver a liquidar.
     */
    public function estaCerrado(): bool
    {
        return $this->estado === EstadoPeriodo::Cerrado;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'periodicidad_pago' => PeriodicidadPago::class,
            'estado' => EstadoPeriodo::class,
            'fecha_inicio' => 'date:Y-m-d',
            'fecha_fin' => 'date:Y-m-d',
            'fecha_liquidacion' => 'datetime',
            'fecha_cierre' => 'datetime',
        ];
    }
}
