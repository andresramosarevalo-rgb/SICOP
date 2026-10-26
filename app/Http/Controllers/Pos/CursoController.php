<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Sede;
use Inertia\Inertia;
use Inertia\Response;

class CursoController extends Controller
{
    /**
     * Muestra los cursos de la sede, los más recientes primero.
     */
    public function index(Sede $sede): Response
    {
        return Inertia::render('Pos/Cursos/Index', [
            'sede' => $sede,
            'cursos' => $sede->cursos()->orderByDesc('fecha')->orderByDesc('id')->get(),
        ]);
    }
}
