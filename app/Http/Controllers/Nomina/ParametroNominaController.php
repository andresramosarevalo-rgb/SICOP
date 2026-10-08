<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\ParametroNomina;
use Inertia\Inertia;
use Inertia\Response;

class ParametroNominaController extends Controller
{
    /**
     * Muestra los años con parámetros legales registrados.
     */
    public function index(): Response
    {
        return Inertia::render('Nomina/Parametros/Index', [
            'parametros' => ParametroNomina::query()->orderByDesc('anio')->get(),
        ]);
    }
}
