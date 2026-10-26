<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Sede;
use App\Models\Soat;
use Inertia\Inertia;
use Inertia\Response;

class SoatController extends Controller
{
    /**
     * Muestra los SOAT de la sede, los más recientes primero.
     */
    public function index(Sede $sede): Response
    {
        return Inertia::render('Pos/Soats/Index', [
            'sede' => $sede,
            'soats' => $sede->soats()->orderByDesc('fecha')->orderByDesc('id')->get(),
        ]);
    }
}
