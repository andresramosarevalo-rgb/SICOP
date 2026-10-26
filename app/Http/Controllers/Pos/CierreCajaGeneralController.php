<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\FiltrarPorDiaRequest;
use App\Models\CierreCaja;
use App\Models\Sede;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class CierreCajaGeneralController extends Controller
{
    /**
     * Muestra el estado del cierre de caja de todas las sedes en un día; sin fecha, el de hoy.
     */
    public function __invoke(FiltrarPorDiaRequest $request): Response
    {
        $fecha = $request->validated('fecha') ?? today()->toDateString();

        $cierres = CierreCaja::query()->whereDate('fecha', $fecha)->get()->keyBy('sede_id');

        $filas = Sede::query()
            ->where(fn (Builder $query) => $query->where('es_activa', true)->orWhereIn('id', $cierres->keys()))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'ciudad'])
            ->map(fn (Sede $sede) => [
                'sede' => $sede,
                'cierre' => $cierres->get($sede->id)?->estaCerrado() ? $cierres->get($sede->id) : null,
            ]);

        return Inertia::render('Pos/Cierre/Index', [
            'fecha' => $fecha,
            'filas' => $filas->values(),
            'valorTotalAConsignar' => $filas->sum(fn (array $fila) => (float) ($fila['cierre']->valor_a_consignar ?? 0)),
        ]);
    }
}
