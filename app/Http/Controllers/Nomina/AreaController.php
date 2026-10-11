<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreAreaRequest;
use App\Http\Requests\Nomina\UpdateAreaRequest;
use App\Models\Area;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AreaController extends Controller
{
    /**
     * Muestra el listado de áreas.
     */
    public function index(): Response
    {
        return Inertia::render('Nomina/Areas/Index', [
            'areas' => Area::query()->orderBy('nombre')->get(['id', 'nombre', 'es_activa']),
        ]);
    }

    /**
     * Registra un área nueva, activa por defecto.
     */
    public function store(StoreAreaRequest $request): RedirectResponse
    {
        Area::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Área creada.']);

        return to_route('nomina.areas.index');
    }

    /**
     * Actualiza el nombre o el estado de un área. Las áreas no se eliminan: se desactivan.
     */
    public function update(UpdateAreaRequest $request, Area $area): RedirectResponse
    {
        $area->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Área actualizada.']);

        return to_route('nomina.areas.index');
    }
}
