<?php

namespace App\Http\Controllers\Pos;

use App\Enums\TipoDocumento;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StoreCursoRequest;
use App\Models\Curso;
use App\Models\Sede;
use Illuminate\Http\RedirectResponse;
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

    /**
     * Muestra el formulario para registrar un curso. Solo las sedes activas registran ventas.
     */
    public function create(Sede $sede): Response
    {
        abort_unless($sede->es_activa, 403, 'La sede está inactiva.');

        return Inertia::render('Pos/Cursos/Create', [
            'sede' => $sede,
            ...$this->opcionesFormulario(),
        ]);
    }

    /**
     * Registra un curso en la sede.
     */
    public function store(StoreCursoRequest $request, Sede $sede): RedirectResponse
    {
        abort_unless($sede->es_activa, 403, 'La sede está inactiva.');

        $sede->cursos()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Curso registrado.']);

        return to_route('pos.sedes.cursos.index', $sede);
    }

    /**
     * Opciones de los selectores del formulario de curso.
     *
     * @return array{tiposDocumento: list<array{valor: string, etiqueta: string}>, porcentajesDescuento: list<int>}
     */
    private function opcionesFormulario(): array
    {
        return [
            'tiposDocumento' => TipoDocumento::opciones(),
            'porcentajesDescuento' => Curso::PORCENTAJES_DESCUENTO,
        ];
    }
}
