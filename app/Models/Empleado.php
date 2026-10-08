<?php

namespace App\Models;

use App\Enums\TipoDocumento;
use Database\Factories\EmpleadoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Expediente de un trabajador de COVIACOL.
 *
 * @property int $id
 * @property TipoDocumento $tipo_documento
 * @property string $numero_documento
 * @property string $nombres
 * @property string $apellidos
 * @property string $email
 * @property string|null $telefono
 * @property string|null $direccion
 * @property Carbon|null $fecha_nacimiento
 * @property int $area_id
 * @property string $cargo
 * @property bool $es_activo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'tipo_documento', 'numero_documento', 'nombres', 'apellidos', 'email',
    'telefono', 'direccion', 'fecha_nacimiento', 'area_id', 'cargo', 'es_activo',
])]
class Empleado extends Model
{
    /** @use HasFactory<EmpleadoFactory> */
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'empleados';

    /**
     * Área a la que pertenece el empleado.
     *
     * @return BelongsTo<Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Contratos del empleado, del más reciente al más antiguo.
     *
     * @return HasMany<Contrato, $this>
     */
    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class)->latest('fecha_inicio');
    }

    /**
     * Contrato vigente del empleado, si lo tiene.
     *
     * @return HasOne<Contrato, $this>
     */
    public function contratoVigente(): HasOne
    {
        return $this->hasOne(Contrato::class)->where('es_vigente', true);
    }

    /**
     * Conceptos recurrentes asignados al empleado.
     *
     * @return HasMany<AsignacionConcepto, $this>
     */
    public function asignacionesConcepto(): HasMany
    {
        return $this->hasMany(AsignacionConcepto::class)->latest('fecha_inicio');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_documento' => TipoDocumento::class,
            'fecha_nacimiento' => 'date:Y-m-d',
            'es_activo' => 'boolean',
        ];
    }
}
