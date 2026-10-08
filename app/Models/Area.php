<?php

namespace App\Models;

use Database\Factories\AreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Área o departamento de COVIACOL al que pertenecen los empleados.
 *
 * @property int $id
 * @property string $nombre
 * @property bool $es_activa
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['nombre', 'es_activa'])]
class Area extends Model
{
    /** @use HasFactory<AreaFactory> */
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'areas';

    /**
     * Limita la consulta a las áreas activas, que son las que se ofrecen al registrar empleados.
     *
     * @param  Builder<Area>  $query
     */
    #[Scope]
    protected function activas(Builder $query): void
    {
        $query->where('es_activa', true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'es_activa' => 'boolean',
        ];
    }
}
