<?php

namespace App\Http\Controllers\Nomina;

use App\Enums\TipoDocumento;
use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreEmpleadoRequest;
use App\Http\Requests\Nomina\UpdateEmpleadoRequest;
use App\Models\Area;
use App\Models\Contrato;
use App\Models\Empleado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Inertia\Inertia;
use Inertia\Response;

class EmpleadoController extends Controller
{
    /**
     * Muestra el listado de empleados, filtrado por documento o nombre (`buscar`) y por estado (`estado`).
     */
    public function index(Request $request): Response
    {
        $busqueda = trim((string) $request->query('buscar', ''));
        $estado = in_array($request->query('estado'), ['activos', 'inactivos'], true) ? $request->query('estado') : '';

        $empleados = Empleado::query()
            ->with('area:id,nombre')
            ->when($busqueda !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereLike('numero_documento', "%{$busqueda}%")
                ->orWhereLike('nombres', "%{$busqueda}%")
                ->orWhereLike('apellidos', "%{$busqueda}%")))
            ->when($estado !== '', fn (Builder $query) => $query->where('es_activo', $estado === 'activos'))
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        return Inertia::render('Nomina/Empleados/Index', [
            'empleados' => $empleados,
            'buscar' => $busqueda,
            'estado' => $estado,
        ]);
    }

    /**
     * Muestra el formulario de registro. Solo se ofrecen las áreas activas.
     */
    public function create(): Response
    {
        return Inertia::render('Nomina/Empleados/Create', $this->opcionesFormulario());
    }

    /**
     * Registra un empleado nuevo, activo por defecto.
     */
    public function store(StoreEmpleadoRequest $request): RedirectResponse
    {
        Empleado::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Empleado registrado.']);

        return to_route('nomina.empleados.index');
    }

    /**
     * Muestra el expediente del empleado con su historial de contratos.
     */
    public function show(Empleado $empleado): Response
    {
        return Inertia::render('Nomina/Empleados/Show', [
            'empleado' => $empleado->load('area:id,nombre'),
            'tipoDocumento' => $empleado->tipo_documento->etiqueta(),
            'contratos' => $empleado->contratos->map(fn (Contrato $contrato) => [
                ...$contrato->toArray(),
                'tipo_contrato' => $contrato->tipo_contrato->etiqueta(),
                'periodicidad_pago' => $contrato->periodicidad_pago->etiqueta(),
            ]),
        ]);
    }

    /**
     * Muestra el formulario de edición. Además de las áreas activas se ofrece el área actual del empleado.
     */
    public function edit(Empleado $empleado): Response
    {
        return Inertia::render('Nomina/Empleados/Edit', [
            'empleado' => $empleado,
            ...$this->opcionesFormulario($empleado->area_id),
        ]);
    }

    /**
     * Actualiza los datos personales y el área del empleado.
     */
    public function update(UpdateEmpleadoRequest $request, Empleado $empleado): RedirectResponse
    {
        $empleado->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Empleado actualizado.']);

        return to_route('nomina.empleados.show', $empleado);
    }

    /**
     * Activa o desactiva al empleado. Un empleado inactivo conserva su expediente.
     */
    public function cambiarEstado(Request $request, Empleado $empleado): RedirectResponse
    {
        $empleado->update($request->validate(['es_activo' => ['required', 'boolean']]));

        $mensaje = $empleado->es_activo ? 'Empleado activado.' : 'Empleado desactivado.';
        Inertia::flash('toast', ['type' => 'success', 'message' => $mensaje]);

        return to_route('nomina.empleados.show', $empleado);
    }

    /**
     * Opciones de los selectores del formulario de empleado.
     *
     * @return array{areas: Collection<int, Area>, tiposDocumento: SupportCollection<int, array{valor: string, etiqueta: string}>}
     */
    private function opcionesFormulario(?int $areaActualId = null): array
    {
        return [
            'areas' => Area::query()
                ->where(fn (Builder $query) => $query->where('es_activa', true)->orWhere('id', $areaActualId))
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
            'tiposDocumento' => collect(TipoDocumento::cases())->map(fn (TipoDocumento $tipo) => [
                'valor' => $tipo->value,
                'etiqueta' => $tipo->etiqueta(),
            ]),
        ];
    }
}
