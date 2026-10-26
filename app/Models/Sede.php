<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\SedeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Sede de COVIACOL donde se dictan los cursos y se vende el SOAT.
 *
 * @property int $id
 * @property string $nombre
 * @property string $ciudad
 * @property string $direccion
 * @property string $valor_base_caja
 * @property bool $es_activa
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['nombre', 'ciudad', 'direccion', 'valor_base_caja', 'es_activa'])]
class Sede extends Model
{
    /** @use HasFactory<SedeFactory> */
    use Auditable, HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'sedes';

    /**
     * Limita la consulta a las sedes activas, que son las que pueden registrar ventas.
     *
     * @param  Builder<Sede>  $query
     */
    #[Scope]
    protected function activas(Builder $query): void
    {
        $query->where('es_activa', true);
    }

    /**
     * Usuario con el que la sede inicia sesión.
     *
     * @return HasOne<User, $this>
     */
    public function usuario(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Cierres de caja diarios de la sede.
     *
     * @return HasMany<CierreCaja, $this>
     */
    public function cierresCaja(): HasMany
    {
        return $this->hasMany(CierreCaja::class);
    }

    /**
     * Cursos dictados en la sede.
     *
     * @return HasMany<Curso, $this>
     */
    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class);
    }

    /**
     * SOAT vendidos en la sede.
     *
     * @return HasMany<Soat, $this>
     */
    public function soats(): HasMany
    {
        return $this->hasMany(Soat::class);
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
            'etiqueta' => 'Sede',
            'descripcion' => $this->nombre,
            'sede_id' => $this->exists ? $this->id : null,
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
            'valor_base_caja' => 'decimal:2',
            'es_activa' => 'boolean',
        ];
    }
}
