<?php

namespace App\Http\Controllers\Nomina;

use App\Actions\Nomina\RegistrarContrato;
use App\Enums\PeriodicidadPago;
use App\Enums\TipoContrato;
use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreContratoRequest;
use App\Models\Empleado;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContratoController extends Controller
{
    /**
     * Muestra el formulario para registrar un contrato del empleado.
     */
    public function create(Empleado $empleado): Response
    {
        return Inertia::render('Nomina/Contratos/Create', [
            'empleado' => $empleado->only(['id', 'nombres', 'apellidos']),
            'contratoVigente' => $empleado->contratoVigente,
            'tiposContrato' => collect(TipoContrato::cases())
                ->map(fn (TipoContrato $tipo) => ['valor' => $tipo->value, 'etiqueta' => $tipo->etiqueta()]),
            'periodicidades' => collect(PeriodicidadPago::cases())
                ->map(fn (PeriodicidadPago $periodicidad) => ['valor' => $periodicidad->value, 'etiqueta' => $periodicidad->etiqueta()]),
        ]);
    }

    /**
     * Registra el contrato como vigente y cierra el anterior, si lo había.
     */
    public function store(StoreContratoRequest $request, Empleado $empleado, RegistrarContrato $registrarContrato): RedirectResponse
    {
        $registrarContrato->ejecutar($empleado, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Contrato registrado.']);

        return to_route('nomina.empleados.show', $empleado);
    }
}
