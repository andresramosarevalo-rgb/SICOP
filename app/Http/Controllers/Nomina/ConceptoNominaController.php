<?php

namespace App\Http\Controllers\Nomina;

use App\Enums\TipoConcepto;
use App\Http\Controllers\Controller;
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
