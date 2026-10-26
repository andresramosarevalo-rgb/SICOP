<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Sede;
use Inertia\Inertia;
use Inertia\Response;

class SedeController extends Controller
{
    /**
     * Muestra las sedes ordenadas por nombre.
     */
    public function index(): Response
    {
        return Inertia::render('Pos/Sedes/Index', [
            'sedes' => Sede::query()->orderBy('nombre')->get(),
        ]);
    }
}
