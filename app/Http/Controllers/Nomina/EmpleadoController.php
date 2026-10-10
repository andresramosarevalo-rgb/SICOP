<?php

namespace App\Http\Controllers\Nomina;

use App\Actions\Nomina\RegistrarContrato;
use App\Enums\PeriodicidadPago;
use App\Enums\TipoContrato;
use App\Enums\TipoDocumento;
use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreEmpleadoRequest;
use App\Http\Requests\Nomina\UpdateEmpleadoRequest;
use App\Models\Area;
use App\Models\ConceptoNomina;
use App\Models\Contrato;
use App\Models\Empleado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
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
            ->with(['area:id,nombre', 'contratoVigente:id,empleado_id,periodicidad_pago'])
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
     * Muestra el formulario de registro del empleado y su contrato. Solo se ofrecen las áreas activas.
     */
    public function create(): Response
    {
        return Inertia::render('Nomina/Empleados/Create', [
            ...$this->opcionesFormulario(),
            'tiposContrato' => collect(TipoContrato::cases())
                ->map(fn (TipoContrato $tipo) => ['valor' => $tipo->value, 'etiqueta' => $tipo->etiqueta()]),
            'periodicidades' => collect(PeriodicidadPago::cases())
                ->map(fn (PeriodicidadPago $periodicidad) => ['valor' => $periodicidad->value, 'etiqueta' => $periodicidad->etiqueta()]),
        ]);
    }

    /**
     * Registra un empleado nuevo, activo por defecto, junto con su contrato vigente.
     * Si el contrato no se puede registrar, tampoco queda el empleado.
     */
    public function store(StoreEmpleadoRequest $request, RegistrarContrato $registrarContrato): RedirectResponse
    {
        $datos = $request->validated();

        $empleado = DB::transaction(function () use ($datos, $registrarContrato): Empleado {
            $empleado = Empleado::create(Arr::except($datos, 'contrato'));
            $registrarContrato->ejecutar($empleado, $datos['contrato']);

            return $empleado;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Empleado y contrato registrados.']);

        return to_route('nomina.empleados.show', $empleado);
    }

    /**
     * Muestra el expediente del empleado con su historial de contratos y sus conceptos recurrentes.
     */
    public function show(Empleado $empleado): Response
    {
        return Inertia::render('Nomina/Empleados/Show', [
            'empleado' => $empleado->load('area:id,nombre'),
            'tipoDocumento' => $empleado->tipo_documento->etiqueta(),
            'contratoVigente' => $empleado->contratoVigente,
            'contratos' => $empleado->contratos->map(fn (Contrato $contrato) => [
                ...$contrato->toArray(),
                'tipo_contrato' => $contrato->tipo_contrato->etiqueta(),
                'periodicidad_pago' => $contrato->periodicidad_pago->etiqueta(),
            ]),
            'asignaciones' => $empleado->asignacionesConcepto()->with('concepto:id,codigo,nombre,tipo,forma_calculo,valor_base,porcentaje_base')->get(),
            'conceptosDisponibles' => ConceptoNomina::query()
                ->where('es_activo', true)->where('es_sistema', false)
                ->orderBy('nombre')->get(['id', 'nombre', 'tipo', 'forma_calculo', 'valor_base', 'porcentaje_base']),
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
