<?php

namespace App\Http\Controllers\Pos;

use App\Actions\Pos\ResumirCajaDelDia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\ConsultarCierreRequest;
use App\Models\CierreCaja;
use App\Models\Sede;
use Inertia\Inertia;
use Inertia\Response;

class CierreCajaController extends Controller
{
    /**
     * Muestra el resumen de caja de la sede en un día; sin fecha, el de hoy.
     */
    public function show(ConsultarCierreRequest $request, Sede $sede, ResumirCajaDelDia $resumirCaja): Response
    {
        $fecha = $request->validated('fecha') ?? today()->toDateString();

        $cierre = CierreCaja::query()->where('sede_id', $sede->id)->whereDate('fecha', $fecha)->first();
        $gastos = $cierre?->gastos()->orderBy('id')->get() ?? collect();
        $resumen = $resumirCaja->ejecutar($sede, $fecha);
        $valorGastos = $gastos->sum(fn ($gasto) => (float) $gasto->valor_gasto);

        return Inertia::render('Pos/Cierre/Show', [
            'sede' => $sede,
            'fecha' => $fecha,
            'resumen' => $resumen,
            'gastos' => $gastos,
            'valorGastos' => $valorGastos,
            'valorEfectivoEsperado' => (float) $resumen['valor_base_caja'] + (float) $resumen['valor_efectivo_ventas'] - (float) $resumen['valor_pago_gestores'] - $valorGastos,
            'estaCerrado' => $cierre?->estaCerrado() ?? false,
        ]);
    }
}
