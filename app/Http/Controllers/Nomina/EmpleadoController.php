<?php

namespace App\Http\Controllers\Nomina;

use App\Enums\TipoDocumento;
use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreEmpleadoRequest;
use App\Models\Area;
use App\Models\Empleado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmpleadoController extends Controller
{
    /**
     * Muestra el listado de empleados, filtrado por documento o nombre cuando se envía `buscar`.
     */
    public function index(Request $request): Response
    {
        $busqueda = trim((string) $request->query('buscar', ''));

        $empleados = Empleado::query()
            ->with('area:id,nombre')
            ->when($busqueda !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereLike('numero_documento', "%{$busqueda}%")
                ->orWhereLike('nombres', "%{$busqueda}%")
                ->orWhereLike('apellidos', "%{$busqueda}%")))
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        return Inertia::render('Nomina/Empleados/Index', [
            'empleados' => $empleados,
            'buscar' => $busqueda,
        ]);
    }

    /**
     * Muestra el formulario de registro. Solo se ofrecen las áreas activas.
     */
    public function create(): Response
    {
        return Inertia::render('Nomina/Empleados/Create', [
            'areas' => Area::activas()->orderBy('nombre')->get(['id', 'nombre']),
            'tiposDocumento' => collect(TipoDocumento::cases())->map(fn (TipoDocumento $tipo) => [
                'valor' => $tipo->value,
                'etiqueta' => $tipo->etiqueta(),
            ]),
        ]);
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
}
