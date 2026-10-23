<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\Rol;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Rol|null $rol
 * @property int|null $sede_id
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, Notifiable;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * Sede a la que pertenece el usuario, si es el usuario de una sede.
     *
     * @return BelongsTo<Sede, $this>
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    /**
     * Cómo se presenta el registro en el historial de auditoría.
     *
     * @return array{modulo: string, etiqueta: string, descripcion: string, sede_id?: int|null}
     */
    public function configuracionAuditoria(): array
    {
        return [
            'modulo' => $this->sede_id === null ? 'admin' : 'pos',
            'etiqueta' => 'Usuario',
            'descripcion' => "{$this->name} · {$this->email}",
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
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol' => Rol::class,
        ];
    }
}
