<?php

namespace App\Http\Controllers\Pos;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\UpdateAccesoSedeRequest;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class SedeAccesoController extends Controller
{
    /**
     * Crea o actualiza el usuario con el que la sede inicia sesión. Cada sede tiene una sola cuenta.
     */
    public function __invoke(UpdateAccesoSedeRequest $request, Sede $sede): RedirectResponse
    {
        $usuario = $sede->usuario ?? new User;

        $usuario->forceFill([
            'name' => $sede->nombre,
            'email' => $request->validated('email'),
            'rol' => Rol::Cajero,
            'sede_id' => $sede->id,
            'email_verified_at' => $usuario->email_verified_at ?? now(),
        ]);

        if ($request->filled('password')) {
            $usuario->password = $request->validated('password');
        }

        $usuario->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Acceso de la sede actualizado.']);

        return to_route('pos.sedes.edit', $sede);
    }
}
