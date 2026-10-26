<?php

namespace App\Http\Controllers\Pos;

use App\Actions\Pos\CerrarCajaDelDia;
use App\Actions\Pos\ReabrirCajaDelDia;
use App\Actions\Pos\ResumirCajaDelDia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\CerrarCajaRequest;
use App\Http\Requests\Pos\ConsultarCierreRequest;
use App\Http\Requests\Pos\ReabrirCajaRequest;
use App\Models\CierreCaja;
use App\Models\Sede;
use Illuminate\Http\RedirectResponse;
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

        if ($cierre?->estaCerrado()) {
            $resumen = [...$resumen, ...$cierre->only(['valor_base_caja', 'valor_efectivo_ventas', 'valor_ventas_datafono', 'valor_ventas_qr', 'valor_pago_gestores'])];
            $valorGastos = (float) $cierre->valor_gastos;
        }

        return Inertia::render('Pos/Cierre/Show', [
            'sede' => $sede,
            'fecha' => $fecha,
            'resumen' => $resumen,
            'gastos' => $gastos,
            'valorGastos' => $valorGastos,
            'valorEfectivoEsperado' => (float) $resumen['valor_base_caja'] + (float) $resumen['valor_efectivo_ventas'] - (float) $resumen['valor_pago_gestores'] - $valorGastos,
            'estaCerrado' => $cierre?->estaCerrado() ?? false,
            'cierre' => $cierre?->estaCerrado() ? $cierre->load('cerradoPor:id,name') : null,
            'consignacion' => $cierre?->estaCerrado() ? $cierre->consignacion : null,
            'cierreId' => $cierre?->id,
        ]);
    }

    /**
     * Cierra la caja del día con el arqueo de la sede.
     */
    public function store(CerrarCajaRequest $request, Sede $sede, CerrarCajaDelDia $cerrarCaja): RedirectResponse
    {
        abort_unless($sede->es_activa, 403, 'La sede está inactiva.');

        $fecha = $request->string('fecha')->toString();

        $cerrarCaja->ejecutar($sede, $fecha, [
            'valor_efectivo_contado' => $request->float('valor_efectivo_contado'),
            'valor_reportado_datafono' => $request->float('valor_reportado_datafono'),
            'valor_reportado_qr' => $request->float('valor_reportado_qr'),
            'justificacion' => $request->string('justificacion')->toString(),
        ], $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Día cerrado.']);

        return to_route('pos.sedes.cierre.show', ['sede' => $sede, 'fecha' => $fecha]);
    }

    /**
     * Reabre el día cerrado de la sede. Solo lo hace el gerente.
     */
    public function reabrir(ReabrirCajaRequest $request, Sede $sede, ReabrirCajaDelDia $reabrirCaja): RedirectResponse
    {
        $fecha = $request->validated('fecha');
        $cierre = CierreCaja::query()->where('sede_id', $sede->id)->whereDate('fecha', $fecha)->firstOrFail();

        $reabrirCaja->ejecutar($cierre, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Día reabierto.']);

        return to_route('pos.sedes.cierre.show', ['sede' => $sede, 'fecha' => $fecha]);
    }
}
