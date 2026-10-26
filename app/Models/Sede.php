<?php

namespace App\Models;

use Database\Factories\SedeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Sede de COVIACOL donde se dictan los cursos y se vende el SOAT.
 *
 * @property int $id
 * @property string $nombre
 * @property string $ciudad
 * @property string $direccion
 * @property bool $es_activa
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['nombre', 'ciudad', 'direccion', 'es_activa'])]
class Sede extends Model
{
    /** @use HasFactory<SedeFactory> */
    use HasFactory;

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
