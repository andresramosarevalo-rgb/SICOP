<?php

namespace App\Http\Controllers\Pos;

use App\Actions\Pos\ResumirCajaDelDia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\ConsultarCierreRequest;
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
        $resumen = $resumirCaja->ejecutar($sede, $fecha);

        return Inertia::render('Pos/Cierre/Show', [
            'sede' => $sede,
            'fecha' => $fecha,
            'resumen' => $resumen,
            'valorEfectivoEsperado' => (float) $resumen['valor_base_caja'] + (float) $resumen['valor_efectivo_ventas'] - (float) $resumen['valor_pago_gestores'],
        ]);
    }
}
