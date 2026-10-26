<?php

namespace App\Concerns;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Deja constancia en el historial de auditoría de cada vez que se crea, modifica o elimina el modelo:
 * quién lo hizo, cuándo y qué valores cambiaron. El modelo describe cómo se presenta en
 * `configuracionAuditoria()`.
 *
 * @phpstan-require-extends Model
 */
trait Auditable
{
    /**
     * Campos que no se muestran en claro: solo se deja constancia de que cambiaron.
     *
     * @var list<string>
     */
    private static array $camposOcultosAuditoria = ['password', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'];

    /**
     * Campos que cambian solos o no aportan al historial.
     *
     * @var list<string>
     */
    private static array $camposIgnoradosAuditoria = ['id', 'created_at', 'updated_at', 'remember_token'];

    /**
     * Engancha el registro de auditoría a los eventos del modelo.
     */
    public static function bootAuditable(): void
    {
        static::created(fn (self $modelo) => $modelo->registrarAuditoria('creado', $modelo->cambiosAlCrearOEliminar(true)));
        static::updated(fn (self $modelo) => $modelo->registrarAuditoria('modificado', $modelo->cambiosAlModificar()));
        static::deleted(fn (self $modelo) => $modelo->registrarAuditoria('eliminado', $modelo->cambiosAlCrearOEliminar(false)));
    }

    /**
     * Cómo se presenta el registro en el historial.
     *
     * @return array{modulo: string, etiqueta: string, descripcion: string, sede_id?: int|null}
     */
    abstract public function configuracionAuditoria(): array;

    /**
     * Guarda la entrada de auditoría; si no cambió nada que valga la pena, no guarda nada.
     *
     * @param  array<string, array{antes: mixed, despues: mixed}>  $cambios
     */
    protected function registrarAuditoria(string $evento, array $cambios): void
    {
        if ($cambios === []) {
            return;
        }

        $configuracion = $this->configuracionAuditoria();

        Auditoria::create([
            'user_id' => auth()->id(),
            'modulo' => $configuracion['modulo'],
            'evento' => $evento,
            'auditable_type' => $this::class,
            'auditable_id' => $this->getKey(),
            'etiqueta' => $configuracion['etiqueta'],
            'descripcion' => Str::limit($configuracion['descripcion'], 250, ''),
            'sede_id' => $configuracion['sede_id'] ?? null,
            'cambios' => $cambios,
        ]);
    }

    /**
     * Valores de un registro nuevo (después) o eliminado (antes).
     *
     * @return array<string, array{antes: mixed, despues: mixed}>
     */
    protected function cambiosAlCrearOEliminar(bool $esCreacion): array
    {
        $cambios = [];

        foreach ($this->getAttributes() as $campo => $valor) {
            if (in_array($campo, self::$camposIgnoradosAuditoria, true)) {
                continue;
            }

            $valor = $this->valorParaAuditoria($campo, $valor);
            $cambios[$campo] = $esCreacion ? ['antes' => null, 'despues' => $valor] : ['antes' => $valor, 'despues' => null];
        }

        return $cambios;
    }

    /**
     * Campos que cambiaron en una modificación, con su valor anterior y el nuevo.
     *
     * @return array<string, array{antes: mixed, despues: mixed}>
     */
    protected function cambiosAlModificar(): array
    {
        $cambios = [];

        foreach ($this->getChanges() as $campo => $nuevo) {
            if (in_array($campo, self::$camposIgnoradosAuditoria, true)) {
                continue;
            }

            if (in_array($campo, self::$camposOcultosAuditoria, true)) {
                $cambios[$campo] = ['antes' => '[oculto]', 'despues' => '[modificado]'];

                continue;
            }

            $cambios[$campo] = [
                'antes' => $this->valorParaAuditoria($campo, $this->getRawOriginal($campo)),
                'despues' => $this->valorParaAuditoria($campo, $nuevo),
            ];
        }

        return $cambios;
    }

    /**
     * Valor listo para guardar: sin secretos y con las fechas sin hora cuando es medianoche.
     */
    protected function valorParaAuditoria(string $campo, mixed $valor): mixed
    {
        if (in_array($campo, self::$camposOcultosAuditoria, true)) {
            return $valor === null ? null : '[oculto]';
        }

        if (is_string($valor) && preg_match('/^(\d{4}-\d{2}-\d{2}) 00:00:00$/', $valor, $coincidencia) === 1) {
            return $coincidencia[1];
        }

        return $valor;
    }
}
