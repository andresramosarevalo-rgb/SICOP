<?php

namespace App\Http\Controllers\Pos;

use App\Enums\CodigoInfraccion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\GuardarTarifaGestorRequest;
use App\Models\Sede;
use App\Models\TarifaGestor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TarifaGestorController extends Controller
{
    /**
     * Muestra la tarifa base de cada código de infracción o, si se elige una sede, el ajuste de esa sede.
     */
    public function index(Request $request): Response
    {
        $sede = Sede::find($request->integer('sede_id'));
        $tarifas = TarifaGestor::query()
            ->whereNull('sede_id')
            ->when($sede, fn ($query) => $query->orWhere('sede_id', $sede->id))
            ->get();

        return Inertia::render('Pos/Tarifas/Index', [
            'sedes' => Sede::query()->orderBy('nombre')->get(['id', 'nombre']),
            'sedeId' => $sede?->id,
            'filas' => collect(CodigoInfraccion::cases())->map(fn (CodigoInfraccion $codigo) => [
                'codigo' => $codigo->value,
                'valor_base' => $tarifas->first(fn (TarifaGestor $tarifa) => $tarifa->sede_id === null && $tarifa->codigo_infraccion === $codigo)->valor_pago_gestor ?? '0.00',
                'ajuste' => $tarifas->first(fn (TarifaGestor $tarifa) => $tarifa->sede_id !== null && $tarifa->codigo_infraccion === $codigo)?->only(['id', 'valor_pago_gestor']),
            ]),
        ]);
    }

    /**
     * Guarda la tarifa base o el ajuste de una sede para un código de infracción.
     */
    public function store(GuardarTarifaGestorRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        TarifaGestor::updateOrCreate(
            ['codigo_infraccion' => $datos['codigo_infraccion'], 'sede_id' => $datos['sede_id'] ?? null],
            ['valor_pago_gestor' => $datos['valor_pago_gestor']],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tarifa guardada.']);

        return to_route('pos.tarifas.index', ['sede_id' => $datos['sede_id'] ?? null]);
    }

    /**
     * Quita el ajuste de una sede para que vuelva a aplicarse la tarifa base. La base no se elimina.
     */
    public function destroy(TarifaGestor $tarifa): RedirectResponse
    {
        abort_if($tarifa->sede_id === null, 403, 'La tarifa base no se puede eliminar.');

        $tarifa->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Ajuste eliminado.']);

        return to_route('pos.tarifas.index', ['sede_id' => $tarifa->sede_id]);
    }
}
