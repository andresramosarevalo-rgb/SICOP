<?php

namespace App\Http\Controllers\Nomina;

use App\Enums\TipoNovedad;
use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreNovedadRequest;
use App\Models\ConceptoNomina;
use App\Models\Empleado;
use App\Models\Novedad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
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

    /**
     * Muestra el formulario para registrar una novedad.
     */
    public function create(): Response
    {
        return Inertia::render('Nomina/Novedades/Create', $this->opcionesFormulario());
    }

    /**
     * Registra la novedad y vuelve al formulario para registrar la siguiente.
     */
    public function store(StoreNovedadRequest $request): RedirectResponse
    {
        Novedad::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Novedad registrada.']);

        return to_route('nomina.novedades.create');
    }

    /**
     * Opciones de los selectores del formulario de novedad.
     *
     * @return array<string, mixed>
     */
    private function opcionesFormulario(): array
    {
        return [
            'empleados' => Empleado::query()->where('es_activo', true)->orderBy('apellidos')->get(['id', 'nombres', 'apellidos']),
            'tipos' => collect(TipoNovedad::cases())->map(fn (TipoNovedad $tipo) => [
                'valor' => $tipo->value,
                'etiqueta' => $tipo->etiqueta(),
                'unidad' => $tipo->unidad(),
                'es_por_dias' => $tipo->esPorDias(),
            ]),
            'conceptos' => ConceptoNomina::query()->where('es_activo', true)->where('es_sistema', false)->orderBy('nombre')->get(['id', 'nombre']),
        ];
    }
}
