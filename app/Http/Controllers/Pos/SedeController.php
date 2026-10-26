<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StoreSedeRequest;
use App\Http\Requests\Pos\UpdateSedeRequest;
use App\Models\Sede;
use Illuminate\Http\RedirectResponse;
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

    /**
     * Muestra el formulario para registrar una sede.
     */
    public function create(): Response
    {
        return Inertia::render('Pos/Sedes/Create');
    }

    /**
     * Registra una sede nueva, activa por defecto.
     */
    public function store(StoreSedeRequest $request): RedirectResponse
    {
        Sede::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sede registrada.']);

        return to_route('pos.sedes.index');
    }

    /**
     * Muestra el formulario de edición de la sede.
     */
    public function edit(Sede $sede): Response
    {
        return Inertia::render('Pos/Sedes/Edit', [
            'sede' => $sede,
        ]);
    }

    /**
     * Actualiza los datos de la sede o la activa y desactiva.
     */
    public function update(UpdateSedeRequest $request, Sede $sede): RedirectResponse
    {
        $sede->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sede actualizada.']);

        return to_route('pos.sedes.index');
    }
}
