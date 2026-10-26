<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FiltrarAuditoriaRequest;
use App\Models\Auditoria;
use App\Models\Sede;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AuditoriaController extends Controller
{
    /**
     * Muestra el historial de cambios, el más reciente primero, con los filtros que se pidan.
     */
    public function __invoke(FiltrarAuditoriaRequest $request): Response
    {
        $filtros = $request->validated();

        $auditorias = Auditoria::query()
            ->with(['usuario:id,name,email', 'sede:id,nombre'])
            ->when($filtros['modulo'] ?? null, fn ($query, string $modulo) => $query->where('modulo', $modulo))
            ->when($filtros['sede_id'] ?? null, fn ($query, string $sedeId) => $query->where('sede_id', $sedeId))
            ->when($filtros['user_id'] ?? null, fn ($query, string $usuarioId) => $query->where('user_id', $usuarioId))
            ->when($filtros['etiqueta'] ?? null, fn ($query, string $etiqueta) => $query->where('etiqueta', $etiqueta))
            ->when($filtros['evento'] ?? null, fn ($query, string $evento) => $query->where('evento', $evento))
            ->when($filtros['desde'] ?? null, fn ($query, string $desde) => $query->whereDate('created_at', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($query, string $hasta) => $query->whereDate('created_at', '<=', $hasta))
            ->latest('created_at')
            ->latest('id')
            ->simplePaginate(50)
            ->withQueryString();

        return Inertia::render('Admin/Auditoria/Index', [
            'auditorias' => $auditorias,
            'filtros' => $filtros,
            'sedes' => Sede::query()->orderBy('nombre')->get(['id', 'nombre']),
            'usuarios' => User::query()->orderBy('name')->get(['id', 'name']),
            'etiquetas' => Auditoria::query()->distinct()->orderBy('etiqueta')->pluck('etiqueta'),
        ]);
    }
}
