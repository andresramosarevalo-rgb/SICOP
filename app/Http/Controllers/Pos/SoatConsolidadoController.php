<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\FiltrarPorDiaRequest;
use App\Models\Soat;
use Inertia\Inertia;
use Inertia\Response;

class SoatConsolidadoController extends Controller
{
    /**
     * Muestra los SOAT de todas las sedes en un día; sin fecha, los de hoy.
     */
    public function __invoke(FiltrarPorDiaRequest $request): Response
    {
        $fecha = $request->validated('fecha') ?? today()->toDateString();

        $soats = Soat::query()
            ->with('sede:id,nombre,ciudad')
            ->whereDate('fecha', $fecha)
            ->orderBy('sede_id')
            ->orderBy('id')
            ->get();

        return Inertia::render('Pos/Soats/Consolidado', [
            'fecha' => $fecha,
            'soats' => $soats,
            'valorTotal' => $soats->sum(fn (Soat $soat) => (float) $soat->valor_soat),
        ]);
    }
}
