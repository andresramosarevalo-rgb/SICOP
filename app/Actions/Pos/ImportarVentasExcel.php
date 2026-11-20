<?php

namespace App\Actions\Pos;

use App\Enums\CodigoInfraccion;
use App\Enums\Referido;
use App\Enums\TipoImportacion;
use App\Http\Requests\Pos\StoreCursoRequest;
use App\Http\Requests\Pos\StoreSoatRequest;
use App\Models\CierreCaja;
use App\Models\Sede;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportarVentasExcel
{
    /**
     * Máximo de errores que se muestran; el resto se resume.
     */
    private const ERRORES_VISIBLES = 25;

    public function __construct(private LeerFilasExcel $leerFilas, private CalcularPagoGestor $calcularPagoGestor) {}

    /**
     * Importa las filas del Excel como registros individuales de la sede. Valida cada fila con las
     * mismas reglas del formulario y, si alguna falla, no guarda nada.
     *
     * @return int Cantidad de registros guardados.
     *
     * @throws ValidationException Con una línea por error: fila y motivo.
     */
    public function ejecutar(Sede $sede, TipoImportacion $tipo, string $ruta): int
    {
        $filas = $this->leerFilas->ejecutar($ruta, $tipo);
        $encabezados = array_column($tipo->columnas(), 'encabezado', 'campo');
        $errores = [];
        $registros = [];

        foreach ($filas as $numero => $fila) {
            /** @var StoreCursoRequest|StoreSoatRequest $solicitud */
            $solicitud = $tipo->solicitud()::create('/', 'POST', $fila);
            $validador = Validator::make($fila, $solicitud->rules(), $solicitud->messages(), $encabezados);
            $validador->after($solicitud->after());
            $solicitud->setValidator($validador);

            if ($validador->fails()) {
                foreach ($validador->errors()->messages() as $campo => $mensajes) {
                    $errores[] = "Fila {$numero} · ".($encabezados[$campo] ?? $campo).': '.$mensajes[0];
                }

                continue;
            }

            if (CierreCaja::diaCerrado($sede->id, $fila['fecha'])) {
                $errores[] = "Fila {$numero} · Fecha: el día {$fila['fecha']} ya está cerrado.";

                continue;
            }

            $registros[] = $solicitud->datosParaGuardar();
        }

        if ($errores !== []) {
            $restantes = count($errores) - self::ERRORES_VISIBLES;

            throw ValidationException::withMessages(['importacion' => implode("\n", [
                'No se guardó nada. Corrija el archivo y vuelva a importarlo:',
                ...array_slice($errores, 0, self::ERRORES_VISIBLES),
                ...($restantes > 0 ? ["… y {$restantes} errores más."] : []),
            ])]);
        }

        DB::transaction(function () use ($sede, $tipo, $registros): void {
            CierreCaja::exigirDiaAbierto($sede->id, ...array_values(array_unique(array_column($registros, 'fecha'))));

            foreach ($registros as $datos) {
                if ($tipo === TipoImportacion::Cursos) {
                    $sede->cursos()->create([...$datos, 'valor_pago_gestor' => $this->calcularPagoGestor->ejecutar(
                        $sede->id,
                        CodigoInfraccion::from($datos['codigo_infraccion']),
                        Referido::tryFrom((string) ($datos['referido'] ?? '')),
                    )]);
                } else {
                    $sede->soats()->create($datos);
                }
            }
        });

        return count($registros);
    }
}
