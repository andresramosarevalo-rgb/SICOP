<?php

namespace App\Http\Controllers\Pos;

use App\Actions\Pos\ConsultarCierresDelDia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\FiltrarPorDiaRequest;
use Inertia\Inertia;
use Inertia\Response;

class CierreCajaGeneralController extends Controller
{
    /**
     * Muestra el estado del cierre de caja de todas las sedes en un día; sin fecha, el de hoy.
     */
    public function __invoke(FiltrarPorDiaRequest $request, ConsultarCierresDelDia $consultarCierres): Response
    {
        $fecha = $request->validated('fecha') ?? today()->toDateString();
        $filas = $consultarCierres->ejecutar($fecha);

        return Inertia::render('Pos/Cierre/Index', [
            'fecha' => $fecha,
            'filas' => $filas,
            'valorTotalAConsignar' => $filas->sum(fn (array $fila) => (float) ($fila['cierre']?->valor_a_consignar ?? 0)),
        ]);
    }
}
