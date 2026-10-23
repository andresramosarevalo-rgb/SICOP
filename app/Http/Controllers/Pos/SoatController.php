<?php

namespace App\Http\Controllers\Pos;

use App\Enums\TipoDocumento;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StoreSoatRequest;
use App\Http\Requests\Pos\UpdateSoatRequest;
use App\Models\Sede;
use App\Models\Soat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SoatController extends Controller
{
    /**
     * Muestra los SOAT de la sede, los más recientes primero.
     */
    public function index(Sede $sede): Response
    {
        Gate::authorize('operar-sede', $sede);

        return Inertia::render('Pos/Soats/Index', [
            'sede' => $sede,
            'soats' => $sede->soats()->orderByDesc('fecha')->orderByDesc('id')->get(),
        ]);
    }

    /**
     * Muestra el formulario para registrar un SOAT. Solo las sedes activas registran ventas.
     */
    public function create(Sede $sede): Response
    {
        Gate::authorize('operar-sede', $sede);
        abort_unless($sede->es_activa, 403, 'La sede está inactiva.');

        return Inertia::render('Pos/Soats/Create', [
            'sede' => $sede,
            'tiposDocumento' => TipoDocumento::opciones(),
        ]);
    }

    /**
     * Registra un SOAT en la sede.
     */
    public function store(StoreSoatRequest $request, Sede $sede): RedirectResponse
    {
        abort_unless($sede->es_activa, 403, 'La sede está inactiva.');

        $sede->soats()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'SOAT registrado.']);

        return to_route('pos.sedes.soats.index', $sede);
    }

    /**
     * Muestra el formulario de edición del SOAT.
     */
    public function edit(Soat $soat): Response
    {
        Gate::authorize('operar-sede', $soat->sede);

        return Inertia::render('Pos/Soats/Edit', [
            'soat' => $soat,
            'sede' => $soat->sede,
            'tiposDocumento' => TipoDocumento::opciones(),
        ]);
    }

    /**
     * Actualiza los datos del SOAT.
     */
    public function update(UpdateSoatRequest $request, Soat $soat): RedirectResponse
    {
        $soat->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'SOAT actualizado.']);

        return to_route('pos.sedes.soats.index', $soat->sede_id);
    }
}
