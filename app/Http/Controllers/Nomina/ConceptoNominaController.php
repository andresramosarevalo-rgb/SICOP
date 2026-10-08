<?php

namespace App\Http\Controllers\Nomina;

use App\Enums\TipoConcepto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreConceptoNominaRequest;
use App\Http\Requests\Nomina\UpdateConceptoNominaRequest;
use App\Models\ConceptoNomina;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConceptoNominaController extends Controller
{
    /**
     * Muestra los conceptos de nómina: primero los devengos y luego las deducciones.
     */
    public function index(): Response
    {
        return Inertia::render('Nomina/Conceptos/Index', [
            'conceptos' => ConceptoNomina::query()
                ->orderByRaw('tipo = ? desc', [TipoConcepto::Devengo->value])
                ->orderByDesc('es_sistema')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    /**
     * Muestra el formulario para registrar un concepto.
     */
    public function create(): Response
    {
        return Inertia::render('Nomina/Conceptos/Create');
    }

    /**
     * Registra un concepto de valor fijo o porcentaje.
     */
    public function store(StoreConceptoNominaRequest $request): RedirectResponse
    {
        ConceptoNomina::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Concepto registrado.']);

        return to_route('nomina.conceptos.index');
    }

    /**
     * Muestra el formulario de edición. Los conceptos de sistema no se editan.
     */
    public function edit(ConceptoNomina $concepto): Response
    {
        abort_if($concepto->es_sistema, 403);

        return Inertia::render('Nomina/Conceptos/Edit', [
            'concepto' => $concepto,
        ]);
    }

    /**
     * Actualiza un concepto que no es de sistema.
     */
    public function update(UpdateConceptoNominaRequest $request, ConceptoNomina $concepto): RedirectResponse
    {
        $concepto->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Concepto actualizado.']);

        return to_route('nomina.conceptos.index');
    }

    /**
     * Elimina un concepto que no es de sistema.
     */
    public function destroy(ConceptoNomina $concepto): RedirectResponse
    {
        abort_if($concepto->es_sistema, 403, 'Los conceptos de sistema no se pueden eliminar.');

        $concepto->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Concepto eliminado.']);

        return to_route('nomina.conceptos.index');
    }
}
