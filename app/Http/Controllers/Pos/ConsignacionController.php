<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StoreConsignacionRequest;
use App\Models\CierreCaja;
use App\Models\Consignacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConsignacionController extends Controller
{
    /**
     * Registra la consignación del efectivo de un día cerrado, con su comprobante. Solo hay una por día.
     */
    public function store(StoreConsignacionRequest $request, CierreCaja $cierre): RedirectResponse
    {
        $datos = $request->validated();
        $soporte = $request->file('soporte');

        $cierre->consignacion()->create([
            'fecha_consignacion' => $datos['fecha_consignacion'],
            'valor_consignado' => $datos['valor_consignado'],
            'referencia' => $datos['referencia'],
            'ruta_soporte' => $soporte->store('soportes-consignacion'),
            'nombre_soporte' => $soporte->getClientOriginalName(),
            'registrado_por' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Consignación registrada.']);

        return to_route('pos.sedes.cierre.show', ['sede' => $cierre->sede_id, 'fecha' => $cierre->fecha->toDateString()]);
    }

    /**
     * Entrega el comprobante de la consignación a quien opera la sede.
     */
    public function soporte(Consignacion $consignacion): StreamedResponse
    {
        Gate::authorize('operar-sede', $consignacion->cierreCaja->sede);

        return Storage::download($consignacion->ruta_soporte, $consignacion->nombre_soporte);
    }
}
