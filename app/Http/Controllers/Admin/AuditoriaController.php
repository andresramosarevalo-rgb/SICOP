<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use Inertia\Inertia;
use Inertia\Response;

class AuditoriaController extends Controller
{
    /**
     * Muestra el historial de cambios, el más reciente primero.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Auditoria/Index', [
            'auditorias' => Auditoria::query()
                ->with(['usuario:id,name,email', 'sede:id,nombre'])
                ->latest('created_at')
                ->latest('id')
                ->simplePaginate(50),
        ]);
    }
}
