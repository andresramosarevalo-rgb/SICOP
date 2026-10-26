<?php

namespace App\Models;

use Database\Factories\AuditoriaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Registro inmutable de un cambio en el sistema: quién lo hizo, cuándo y qué cambió.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $modulo
 * @property string $evento
 * @property string $auditable_type
 * @property int $auditable_id
 * @property string $etiqueta
 * @property string $descripcion
 * @property int|null $sede_id
 * @property array<string, array{antes: mixed, despues: mixed}> $cambios
 * @property Carbon|null $created_at
 */
#[Fillable(['user_id', 'modulo', 'evento', 'auditable_type', 'auditable_id', 'etiqueta', 'descripcion', 'sede_id', 'cambios'])]
class Auditoria extends Model
{
    /** @use HasFactory<AuditoriaFactory> */
    use HasFactory;

    /**
     * El registro de auditoría no se modifica: solo tiene fecha de creación.
     */
    public const UPDATED_AT = null;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'auditorias';

    /**
     * Usuario que hizo el cambio; nulo si lo hizo el sistema (seeders, consola).
     *
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Sede a la que pertenece el registro cambiado, si aplica.
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
            'cambios' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
