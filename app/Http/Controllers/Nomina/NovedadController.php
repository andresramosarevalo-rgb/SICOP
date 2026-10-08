<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use App\Models\Novedad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NovedadController extends Controller
{
    /**
     * Muestra las novedades, filtradas por empleado y por rango de fechas.
     */
    public function index(Request $request): Response
    {
        $filtros = $request->validate([
            'empleado_id' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $novedades = Novedad::query()
            ->with(['empleado:id,nombres,apellidos', 'concepto:id,nombre'])
            ->when($filtros['empleado_id'] ?? null, fn (Builder $query, int $id) => $query->where('empleado_id', $id))
            ->when($filtros['desde'] ?? null, fn (Builder $query, string $desde) => $query
                ->where(fn (Builder $query) => $query->where('fecha_fin', '>=', $desde)
                    ->orWhere(fn (Builder $query) => $query->whereNull('fecha_fin')->where('fecha_inicio', '>=', $desde))))
            ->when($filtros['hasta'] ?? null, fn (Builder $query, string $hasta) => $query->where('fecha_inicio', '<=', $hasta))
            ->latest('fecha_inicio')
            ->get()
            ->map(fn (Novedad $novedad) => [...$novedad->toArray(), 'tipo_etiqueta' => $novedad->tipo->etiqueta(), 'esta_liquidada' => $novedad->estaLiquidada()]);

        return Inertia::render('Nomina/Novedades/Index', [
            'novedades' => $novedades,
            'filtros' => $filtros,
            'empleados' => Empleado::query()->orderBy('apellidos')->get(['id', 'nombres', 'apellidos']),
        ]);
    }
}
