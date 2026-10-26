<?php

namespace App\Http\Controllers\Pos;

use App\Enums\FormaPago;
use App\Enums\TipoDocumento;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StoreSoatRequest;
use App\Http\Requests\Pos\UpdateSoatRequest;
use App\Models\CierreCaja;
use App\Models\Sede;
use App\Models\Soat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SoatController extends Controller
{
    /**
     * Muestra los SOAT de la sede, los más recientes primero.
     */
    public function index(Sede $sede): Response
    {
        Gate::authorize('operar-sede', $sede);

        return Inertia::render('Pos/Soats/Index', [
            'sede' => $sede,
            'soats' => $sede->soats()->orderByDesc('fecha')->orderByDesc('id')->get(),
        ]);
    }

    /**
     * Muestra el formulario para registrar un SOAT. Solo las sedes activas registran ventas.
     */
    public function create(Sede $sede): Response
    {
        Gate::authorize('operar-sede', $sede);
        abort_unless($sede->es_activa, 403, 'La sede está inactiva.');

        return Inertia::render('Pos/Soats/Create', [
            'sede' => $sede,
            'tiposDocumento' => TipoDocumento::opciones(),
            'formasPago' => FormaPago::opciones(FormaPago::PARA_SOAT),
        ]);
    }

    /**
     * Registra un SOAT en la sede.
     */
    public function store(StoreSoatRequest $request, Sede $sede): RedirectResponse
    {
        abort_unless($sede->es_activa, 403, 'La sede está inactiva.');
        abort_if(CierreCaja::diaCerrado($sede->id, $request->validated('fecha')), 403, 'El día está cerrado.');

        $sede->soats()->create($request->datosParaGuardar());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'SOAT registrado.']);

        return to_route('pos.sedes.soats.index', $sede);
    }

    /**
     * Muestra el formulario de edición del SOAT.
     */
    public function edit(Soat $soat): Response
    {
        Gate::authorize('operar-sede', $soat->sede);
        abort_if(CierreCaja::diaCerrado($soat->sede_id, $soat->fecha), 403, 'El día está cerrado.');

        return Inertia::render('Pos/Soats/Edit', [
            'soat' => $soat,
            'sede' => $soat->sede,
            'tiposDocumento' => TipoDocumento::opciones(),
            'formasPago' => FormaPago::opciones(FormaPago::PARA_SOAT),
        ]);
    }

    /**
     * Actualiza los datos del SOAT.
     */
    public function update(UpdateSoatRequest $request, Soat $soat): RedirectResponse
    {
        abort_if(CierreCaja::diaCerrado($soat->sede_id, $soat->fecha) || CierreCaja::diaCerrado($soat->sede_id, $request->validated('fecha')), 403, 'El día está cerrado.');

        $soat->update($request->datosParaGuardar());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'SOAT actualizado.']);

        return to_route('pos.sedes.soats.index', $soat->sede_id);
    }
}
