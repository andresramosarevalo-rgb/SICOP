<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Limita las consultas de un modelo con `sede_id` a la sede del usuario; el super administrador ve todas.
 */
trait FiltraPorSede
{
    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function visiblesPara(Builder $query, User $usuario): void
    {
        if ($usuario->esSuperAdmin()) {
            return;
        }

        $usuario->sede_id === null
            ? $query->whereRaw('1 = 0')
            : $query->where($query->qualifyColumn('sede_id'), $usuario->sede_id);
    }
}
