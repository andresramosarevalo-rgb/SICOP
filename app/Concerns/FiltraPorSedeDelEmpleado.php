<?php

namespace App\Concerns;

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Limita las consultas de un modelo que pertenece a un empleado a los empleados de la sede del usuario;
 * el super administrador ve todos.
 */
trait FiltraPorSedeDelEmpleado
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

        $query->whereHas('empleado', fn (Builder $empleados) => $empleados->visiblesPara($usuario));
    }
}
