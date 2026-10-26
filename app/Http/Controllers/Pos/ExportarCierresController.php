<?php

namespace App\Http\Controllers\Pos;

use App\Actions\Pos\ConsultarCierresDelDia;
use App\Actions\Pos\EscribirCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\FiltrarPorDiaRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportarCierresController extends Controller
{
    /**
     * Descarga en CSV el cierre de caja de todas las sedes en un día; sin fecha, el de hoy. Las
     * sedes que aún no cerraron aparecen como "Sin cerrar".
     */
    public function __invoke(FiltrarPorDiaRequest $request, ConsultarCierresDelDia $consultarCierres, EscribirCsv $escribirCsv): StreamedResponse
    {
        $fecha = $request->validated('fecha') ?? today()->toDateString();

        return $escribirCsv->ejecutar(
            "cierres-de-caja-{$fecha}.csv",
            [
                'Fecha', 'Sede', 'Ciudad', 'Estado', 'Base de caja', 'Ventas en efectivo', 'Pago a gestores', 'Gastos',
                'Efectivo esperado', 'Efectivo contado', 'Diferencia en efectivo', 'Ventas en datáfono',
                'Reportado en datáfono', 'Diferencia en datáfono', 'Ventas en QR', 'Reportado en QR',
                'Diferencia en QR', 'A consignar', 'Justificación', 'Cerrado por', 'Cerrado el',
                'Valor consignado', 'Fecha de consignación', 'Referencia de consignación',
            ],
            $consultarCierres->ejecutar($fecha)->map(function (array $fila) use ($fecha): array {
                $cierre = $fila['cierre'];
                $consignacion = $cierre?->consignacion;
                $numero = fn (?string $valor): ?float => $valor === null ? null : (float) $valor;

                return [
                    $fecha,
                    $fila['sede']->nombre,
                    $fila['sede']->ciudad,
                    $cierre === null ? 'Sin cerrar' : 'Cerrado',
                    $numero($cierre?->valor_base_caja),
                    $numero($cierre?->valor_efectivo_ventas),
                    $numero($cierre?->valor_pago_gestores),
                    $numero($cierre?->valor_gastos),
                    $numero($cierre?->valor_efectivo_esperado),
                    $numero($cierre?->valor_efectivo_contado),
                    $numero($cierre?->valor_diferencia_efectivo),
                    $numero($cierre?->valor_ventas_datafono),
                    $numero($cierre?->valor_reportado_datafono),
                    $numero($cierre?->valor_diferencia_datafono),
                    $numero($cierre?->valor_ventas_qr),
                    $numero($cierre?->valor_reportado_qr),
                    $numero($cierre?->valor_diferencia_qr),
                    $numero($cierre?->valor_a_consignar),
                    $cierre?->justificacion,
                    $cierre?->cerradoPor?->name,
                    $cierre?->cerrado_en?->format('Y-m-d H:i'),
                    $numero($consignacion?->valor_consignado),
                    $consignacion?->fecha_consignacion->toDateString(),
                    $consignacion?->referencia,
                ];
            }),
        );
    }
}
