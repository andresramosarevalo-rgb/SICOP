<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\FiltrarPorDiaRequest;
use App\Models\Curso;
use Inertia\Inertia;
use Inertia\Response;

class CursoConsolidadoController extends Controller
{
    /**
     * Muestra los cursos de todas las sedes en un día; sin fecha, los de hoy.
     */
    public function __invoke(FiltrarPorDiaRequest $request): Response
    {
        $fecha = $request->validated('fecha') ?? today()->toDateString();

        $cursos = Curso::query()
            ->with('sede:id,nombre,ciudad')
            ->whereDate('fecha', $fecha)
            ->orderBy('sede_id')
            ->orderBy('id')
            ->get();

        return Inertia::render('Pos/Cursos/Consolidado', [
            'fecha' => $fecha,
            'cursos' => $cursos,
            'valorTotal' => $cursos->sum(fn (Curso $curso) => (float) $curso->valor_curso),
        ]);
    }
}
