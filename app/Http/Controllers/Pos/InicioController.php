<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InicioController extends Controller
{
    /**
     * Muestra la portada de POS al gerente; el usuario de una sede va directo a los cursos de su sede.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if ($request->user()->can('gestionar-pos')) {
            return Inertia::render('Pos/Inicio/Index');
        }

        return to_route('pos.sedes.cursos.index', $request->user()->sede_id);
    }
}
