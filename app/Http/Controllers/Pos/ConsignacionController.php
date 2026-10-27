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
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ConsignacionController extends Controller
{
    /**
     * Registra la consignación del efectivo de un día cerrado, con su comprobante. Solo hay una por día.
     */
    public function store(StoreConsignacionRequest $request, CierreCaja $cierre): RedirectResponse
    {
        $datos = $request->validated();
        $soporte = $request->file('soporte');

        $rutaSoporte = $soporte->store('soportes-consignacion') ?: throw new RuntimeException('No se pudo guardar el soporte.');

        try {
            $cierre->consignacion()->create([
                'fecha_consignacion' => $datos['fecha_consignacion'],
                'valor_consignado' => $datos['valor_consignado'],
                'referencia' => $datos['referencia'],
                'ruta_soporte' => $rutaSoporte,
                'nombre_soporte' => $soporte->getClientOriginalName(),
                'registrado_por' => $request->user()->id,
            ]);
        } catch (Throwable $excepcion) {
            Storage::delete($rutaSoporte);

            throw $excepcion;
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Consignación registrada.']);

        return to_route('pos.sedes.cierre.show', ['sede' => $cierre->sede_id, 'fecha' => $cierre->fecha->toDateString()]);
    }

    /**
     * Elimina una consignación registrada por error, junto con su comprobante.
     */
    public function destroy(Consignacion $consignacion): RedirectResponse
    {
        Gate::authorize('gestionar-pos');

        $cierre = $consignacion->cierreCaja;

        $consignacion->delete();
        Storage::delete($consignacion->ruta_soporte);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Consignación eliminada.']);

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
