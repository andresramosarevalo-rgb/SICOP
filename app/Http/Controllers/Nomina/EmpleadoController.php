<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use Illuminate\Database\Eloquent\Builder;
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
}
