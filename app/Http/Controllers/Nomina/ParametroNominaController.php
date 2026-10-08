<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreParametroNominaRequest;
use App\Http\Requests\Nomina\UpdateParametroNominaRequest;
use App\Models\ParametroNomina;
use Illuminate\Http\RedirectResponse;
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

    /**
     * Muestra el formulario para registrar los parámetros de un año nuevo.
     */
    public function create(): Response
    {
        return Inertia::render('Nomina/Parametros/Create');
    }

    /**
     * Registra los parámetros de un año.
     */
    public function store(StoreParametroNominaRequest $request): RedirectResponse
    {
        ParametroNomina::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Parámetros registrados.']);

        return to_route('nomina.parametros.index');
    }

    /**
     * Muestra el formulario de edición de los parámetros de un año.
     */
    public function edit(ParametroNomina $parametro): Response
    {
        return Inertia::render('Nomina/Parametros/Edit', [
            'parametro' => $parametro,
        ]);
    }

    /**
     * Actualiza los parámetros de un año.
     */
    public function update(UpdateParametroNominaRequest $request, ParametroNomina $parametro): RedirectResponse
    {
        $parametro->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Parámetros actualizados.']);

        return to_route('nomina.parametros.index');
    }
}
