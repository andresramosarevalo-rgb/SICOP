<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StoreGastoCajaRequest;
use App\Models\CierreCaja;
use App\Models\Sede;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class GastoCajaController extends Controller
{
    /**
     * Registra un gasto del día con su soporte. El soporte queda en el disco privado.
     */
    public function store(StoreGastoCajaRequest $request, Sede $sede): RedirectResponse
    {
        abort_unless($sede->es_activa, 403, 'La sede está inactiva.');

        $datos = $request->validated();
        $cierre = CierreCaja::query()->where('sede_id', $sede->id)->whereDate('fecha', $datos['fecha'])->first()
            ?? CierreCaja::create(['sede_id' => $sede->id, 'fecha' => $datos['fecha']]);

        abort_if($cierre->estaCerrado(), 403, 'El día está cerrado.');

        $soporte = $request->file('soporte');

        $cierre->gastos()->create([
            'concepto' => $datos['concepto'],
            'valor_gasto' => $datos['valor_gasto'],
            'ruta_soporte' => $soporte->store('soportes-caja'),
            'nombre_soporte' => $soporte->getClientOriginalName(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Gasto registrado.']);

        return to_route('pos.sedes.cierre.show', ['sede' => $sede, 'fecha' => $datos['fecha']]);
    }
}
