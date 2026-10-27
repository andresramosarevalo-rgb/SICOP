<?php

namespace App\Actions\Pos;

use App\Enums\EstadoCierre;
use App\Models\CierreCaja;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CerrarCajaDelDia
{
    public function __construct(private ResumirCajaDelDia $resumirCaja) {}

    /**
     * Cierra el día de una sede: calcula el arqueo con las ventas y los gastos del día, congela
     * las cifras y deja constancia de quién cerró. Si el efectivo, el datáfono o el QR no cuadran,
     * exige la justificación.
     *
     * @param  array{valor_efectivo_contado: string|int|float, valor_reportado_datafono: string|int|float, valor_reportado_qr: string|int|float, justificacion?: string|null}  $datos
     *
     * @throws ValidationException
     */
    public function ejecutar(Sede $sede, string $fecha, array $datos, User $usuario): CierreCaja
    {
        return DB::transaction(function () use ($sede, $fecha, $datos, $usuario): CierreCaja {
            Sede::query()->whereKey($sede->id)->lockForUpdate()->first();

            $cierre = CierreCaja::query()->where('sede_id', $sede->id)->whereDate('fecha', $fecha)->lockForUpdate()->first()
                ?? CierreCaja::create(['sede_id' => $sede->id, 'fecha' => $fecha]);

            abort_if($cierre->estaCerrado(), 403, 'El día ya está cerrado.');

            $resumen = $this->resumirCaja->ejecutar($sede, $fecha);
            $gastos = $this->centavos($cierre->gastos()->sum('valor_gasto'));

            $base = $this->centavos($resumen['valor_base_caja']);
            $esperado = $base + $this->centavos($resumen['valor_efectivo_ventas']) - $this->centavos($resumen['valor_pago_gestores']) - $gastos;
            $contado = $this->centavos($datos['valor_efectivo_contado']);
            $reportadoDatafono = $this->centavos($datos['valor_reportado_datafono']);
            $reportadoQr = $this->centavos($datos['valor_reportado_qr']);

            $diferenciaEfectivo = $contado - $esperado;
            $diferenciaDatafono = $reportadoDatafono - $this->centavos($resumen['valor_ventas_datafono']);
            $diferenciaQr = $reportadoQr - $this->centavos($resumen['valor_ventas_qr']);

            $justificacion = trim((string) ($datos['justificacion'] ?? ''));

            if (($diferenciaEfectivo !== 0 || $diferenciaDatafono !== 0 || $diferenciaQr !== 0) && $justificacion === '') {
                throw ValidationException::withMessages(['justificacion' => 'Hay diferencias en la caja: escriba la justificación.']);
            }

            $cierre->update([
                'estado' => EstadoCierre::Cerrado,
                'valor_base_caja' => $this->pesos($base),
                'valor_efectivo_ventas' => $resumen['valor_efectivo_ventas'],
                'valor_pago_gestores' => $resumen['valor_pago_gestores'],
                'valor_gastos' => $this->pesos($gastos),
                'valor_efectivo_esperado' => $this->pesos($esperado),
                'valor_efectivo_contado' => $this->pesos($contado),
                'valor_diferencia_efectivo' => $this->pesos($diferenciaEfectivo),
                'valor_ventas_datafono' => $resumen['valor_ventas_datafono'],
                'valor_reportado_datafono' => $this->pesos($reportadoDatafono),
                'valor_diferencia_datafono' => $this->pesos($diferenciaDatafono),
                'valor_ventas_qr' => $resumen['valor_ventas_qr'],
                'valor_reportado_qr' => $this->pesos($reportadoQr),
                'valor_diferencia_qr' => $this->pesos($diferenciaQr),
                'valor_a_consignar' => $this->pesos($contado - $base),
                'justificacion' => $justificacion === '' ? null : $justificacion,
                'cerrado_por' => $usuario->id,
                'cerrado_en' => now(),
            ]);

            return $cierre;
        });
    }

    /**
     * Pasa un valor en pesos a centavos enteros para sumar y comparar sin errores de redondeo.
     */
    private function centavos(float|int|string $valor): int
    {
        return (int) round((float) $valor * 100);
    }

    /**
     * Pasa centavos a pesos con dos decimales.
     */
    private function pesos(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }
}
