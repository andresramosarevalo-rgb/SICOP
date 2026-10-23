<?php

namespace App\Http\Controllers\Pos;

use App\Actions\Pos\EscribirCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\FiltrarPorDiaRequest;
use App\Models\Curso;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportarCursosController extends Controller
{
    /**
     * Descarga en CSV los cursos de todas las sedes en un día; sin fecha, los de hoy.
     */
    public function __invoke(FiltrarPorDiaRequest $request, EscribirCsv $escribirCsv): StreamedResponse
    {
        $fecha = $request->validated('fecha') ?? today()->toDateString();

        $cursos = Curso::query()
            ->with('sede:id,nombre,ciudad')
            ->whereDate('fecha', $fecha)
            ->orderBy('sede_id')
            ->orderBy('id')
            ->cursor();

        return $escribirCsv->ejecutar(
            "cursos-{$fecha}.csv",
            [
                'Fecha', 'Sede', 'Ciudad', 'Tipo de documento', 'Número de documento', 'Cliente', 'Celular', 'Correo',
                'Número de certificado', 'Número de comparendo', 'Secretaría de tránsito', 'Hora de inicio',
                'Hora de fin', 'Tipo de comparendo', 'Valor del curso', 'Descuento (%)', 'Forma de pago',
                'Valor en efectivo', 'Valor en datáfono', 'Valor en QR', 'Referido', 'Pago al gestor',
            ],
            $cursos->map(fn (Curso $curso) => [
                $curso->fecha->toDateString(),
                $curso->sede->nombre,
                $curso->sede->ciudad,
                $curso->tipo_documento->value,
                $curso->numero_documento,
                $curso->nombre_cliente,
                $curso->celular,
                $curso->correo,
                $curso->numero_certificado,
                $curso->numero_comparendo,
                $curso->secretaria_transito,
                substr($curso->hora_inicio, 0, 5),
                substr($curso->hora_fin, 0, 5),
                $curso->codigo_infraccion->value,
                (float) $curso->valor_curso,
                (float) $curso->porcentaje_descuento,
                $curso->forma_pago->etiqueta(),
                (float) $curso->valor_efectivo,
                (float) $curso->valor_datafono,
                (float) $curso->valor_qr,
                $curso->referido?->etiqueta() ?? '',
                (float) $curso->valor_pago_gestor,
            ]),
        );
    }
}
