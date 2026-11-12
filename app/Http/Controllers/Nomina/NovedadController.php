<?php

namespace App\Http\Controllers\Nomina;

use App\Enums\TipoNovedad;
use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreNovedadRequest;
use App\Http\Requests\Nomina\UpdateNovedadRequest;
use App\Models\ConceptoNomina;
use App\Models\Empleado;
use App\Models\Novedad;
use App\Models\User;
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
            ->visiblesPara($request->user())
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
            'empleados' => Empleado::query()->visiblesPara($request->user())->orderBy('apellidos')->get(['id', 'nombres', 'apellidos']),
        ]);
    }

    /**
     * Muestra el formulario para registrar una novedad.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Nomina/Novedades/Create', $this->opcionesFormulario($request->user()));
    }

    /**
     * Registra la novedad y vuelve al formulario para registrar la siguiente.
     */
    public function store(StoreNovedadRequest $request): RedirectResponse
    {
        Novedad::create($request->datosParaGuardar());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Novedad registrada.']);

        return to_route('nomina.novedades.create');
    }

    /**
     * Muestra el formulario de edición de una novedad que aún no se ha liquidado.
     */
    public function edit(Request $request, Novedad $novedad): Response
    {
        abort_if($novedad->estaLiquidada(), 403, 'La novedad ya fue liquidada.');

        return Inertia::render('Nomina/Novedades/Edit', ['novedad' => $novedad, ...$this->opcionesFormulario($request->user())]);
    }

    /**
     * Actualiza una novedad que aún no se ha liquidado.
     */
    public function update(UpdateNovedadRequest $request, Novedad $novedad): RedirectResponse
    {
        $novedad->update($request->datosParaGuardar());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Novedad actualizada.']);

        return to_route('nomina.novedades.index');
    }

    /**
     * Elimina una novedad que aún no se ha liquidado.
     */
    public function destroy(Novedad $novedad): RedirectResponse
    {
        abort_if($novedad->estaLiquidada(), 403, 'La novedad ya fue liquidada.');

        $novedad->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Novedad eliminada.']);

        return to_route('nomina.novedades.index');
    }

    /**
     * Opciones de los selectores del formulario de novedad.
     *
     * @return array<string, mixed>
     */
    private function opcionesFormulario(User $usuario): array
    {
        return [
            'empleados' => Empleado::query()->visiblesPara($usuario)->where('es_activo', true)->orderBy('apellidos')->get(['id', 'nombres', 'apellidos']),
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
