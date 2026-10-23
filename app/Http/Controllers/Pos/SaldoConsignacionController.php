<?php

namespace App\Http\Controllers\Pos;

use App\Enums\EstadoCierre;
use App\Http\Controllers\Controller;
use App\Models\CierreCaja;
use App\Models\Sede;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaldoConsignacionController extends Controller
{
    /**
     * Muestra lo que cada sede debe consignar y lo que ya consignó; al elegir una sede, el detalle por día.
     */
    public function __invoke(Request $request): Response
    {
        $sede = Sede::find($request->integer('sede_id'));

        $saldos = CierreCaja::query()
            ->leftJoin('consignaciones', 'consignaciones.cierre_caja_id', '=', 'cierres_caja.id')
            ->where('cierres_caja.estado', EstadoCierre::Cerrado)
            ->groupBy('cierres_caja.sede_id')
            ->select('cierres_caja.sede_id')
            ->selectRaw('coalesce(sum(cierres_caja.valor_a_consignar), 0) as valor_a_consignar')
            ->selectRaw('coalesce(sum(consignaciones.valor_consignado), 0) as valor_consignado')
            ->selectRaw('coalesce(sum(case when consignaciones.id is null and cierres_caja.valor_a_consignar > 0 then 1 else 0 end), 0) as dias_sin_consignar')
            ->get()
            ->keyBy('sede_id');

        $filas = Sede::query()->orderBy('nombre')->get(['id', 'nombre', 'ciudad', 'es_activa'])
            ->map(fn (Sede $fila) => [
                'sede' => $fila,
                'valor_a_consignar' => number_format((float) ($saldos->get($fila->id)?->valor_a_consignar ?? 0), 2, '.', ''),
                'valor_consignado' => number_format((float) ($saldos->get($fila->id)?->valor_consignado ?? 0), 2, '.', ''),
                'dias_sin_consignar' => (int) ($saldos->get($fila->id)?->dias_sin_consignar ?? 0),
            ])
            ->filter(fn (array $fila) => $fila['sede']->es_activa || (float) $fila['valor_a_consignar'] !== 0.0)
            ->values();

        return Inertia::render('Pos/Consignaciones/Index', [
            'filas' => $filas,
            'sedeId' => $sede?->id,
            'dias' => $sede === null ? [] : CierreCaja::query()
                ->with('consignacion')
                ->where('sede_id', $sede->id)
                ->where('estado', EstadoCierre::Cerrado)
                ->orderByDesc('fecha')
                ->limit(90)
                ->get(['id', 'sede_id', 'fecha', 'valor_a_consignar']),
        ]);
    }
}
