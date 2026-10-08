<?php

namespace App\Http\Controllers\Nomina;

use App\Actions\Nomina\LiquidarPeriodoNomina;
use App\Http\Controllers\Controller;
use App\Models\PeriodoNomina;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PeriodoNominaController extends Controller
{
    /**
     * Muestra los periodos de nómina, del más reciente al más antiguo.
     */
    public function index(): Response
    {
        return Inertia::render('Nomina/Periodos/Index', [
            'periodos' => PeriodoNomina::query()
                ->withCount('recibos')
                ->withSum('recibos', 'valor_neto')
                ->latest('fecha_inicio')
                ->get(),
        ]);
    }

    /**
     * Muestra el periodo con los recibos de su última liquidación.
     */
    public function show(PeriodoNomina $periodo): Response
    {
        return Inertia::render('Nomina/Periodos/Show', [
            'periodo' => $periodo->load('liquidador:id,name'),
            'recibos' => $periodo->recibos()->with('empleado:id,nombres,apellidos,numero_documento')->get(),
        ]);
    }

    /**
     * Liquida (o vuelve a liquidar) el periodo.
     */
    public function liquidar(Request $request, PeriodoNomina $periodo, LiquidarPeriodoNomina $liquidarPeriodo): RedirectResponse
    {
        $liquidarPeriodo->ejecutar($periodo, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Periodo liquidado.']);

        return to_route('nomina.periodos.show', $periodo);
    }
}
