<?php

namespace App\Http\Controllers\Pos;

use App\Actions\Pos\EscribirCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\FiltrarPorDiaRequest;
use App\Models\Soat;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportarSoatsController extends Controller
{
    /**
     * Descarga en CSV los SOAT de todas las sedes en un día; sin fecha, los de hoy.
     */
    public function __invoke(FiltrarPorDiaRequest $request, EscribirCsv $escribirCsv): StreamedResponse
    {
        $fecha = $request->validated('fecha') ?? today()->toDateString();

        $soats = Soat::query()
            ->with('sede:id,nombre,ciudad')
            ->whereDate('fecha', $fecha)
            ->orderBy('sede_id')
            ->orderBy('id')
            ->cursor();

        return $escribirCsv->ejecutar(
            "soat-{$fecha}.csv",
            [
                'Fecha', 'Sede', 'Ciudad', 'Tipo de documento', 'Número de documento', 'Cliente', 'Celular', 'Correo',
                'Placa', 'Valor del SOAT', 'Forma de pago', 'Valor en efectivo', 'Valor en QR',
            ],
            $soats->map(fn (Soat $soat) => [
                $soat->fecha->toDateString(),
                $soat->sede->nombre,
                $soat->sede->ciudad,
                $soat->tipo_documento->value,
                $soat->numero_documento,
                $soat->nombre_cliente,
                $soat->celular,
                $soat->correo,
                $soat->placa,
                (float) $soat->valor_soat,
                $soat->forma_pago->etiqueta(),
                (float) $soat->valor_efectivo,
                (float) $soat->valor_qr,
            ]),
        );
    }
}
