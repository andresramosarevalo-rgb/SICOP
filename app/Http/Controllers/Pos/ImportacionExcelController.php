<?php

namespace App\Http\Controllers\Pos;

use App\Actions\Pos\GenerarPlantillaExcel;
use App\Actions\Pos\ImportarVentasExcel;
use App\Enums\TipoImportacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\ImportarExcelRequest;
use App\Models\Sede;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportacionExcelController extends Controller
{
    /**
     * Descarga la plantilla de Excel para registrar cursos o SOAT de varias ventas a la vez.
     */
    public function plantilla(Sede $sede, TipoImportacion $tipo, GenerarPlantillaExcel $generarPlantilla): StreamedResponse
    {
        Gate::authorize('operar-sede', $sede);

        return $generarPlantilla->ejecutar($tipo);
    }

    /**
     * Importa el Excel: cada fila se guarda como un curso o SOAT individual de la sede.
     */
    public function store(ImportarExcelRequest $request, Sede $sede, TipoImportacion $tipo, ImportarVentasExcel $importar): RedirectResponse
    {
        abort_unless($sede->es_activa, 403, 'La sede está inactiva.');

        $cantidad = $importar->ejecutar($sede, $tipo, $request->file('archivo')->getRealPath());

        Inertia::flash('toast', ['type' => 'success', 'message' => $cantidad === 1
            ? '1 registro importado.'
            : "{$cantidad} registros importados."]);

        return to_route("pos.sedes.{$tipo->value}.index", $sede);
    }
}
