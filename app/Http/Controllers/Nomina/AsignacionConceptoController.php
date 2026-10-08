<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreAsignacionConceptoRequest;
use App\Models\AsignacionConcepto;
use App\Models\Empleado;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class AsignacionConceptoController extends Controller
{
    /**
     * Asigna un concepto recurrente al empleado.
     */
    public function store(StoreAsignacionConceptoRequest $request, Empleado $empleado): RedirectResponse
    {
        $empleado->asignacionesConcepto()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Concepto asignado.']);

        return to_route('nomina.empleados.show', $empleado);
    }

    /**
     * Retira un concepto recurrente del empleado.
     */
    public function destroy(AsignacionConcepto $asignacion): RedirectResponse
    {
        $asignacion->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Concepto retirado.']);

        return to_route('nomina.empleados.show', $asignacion->empleado_id);
    }
}
