<?php

namespace App\Actions\Pos;

use App\Enums\EstadoCierre;
use App\Models\CierreCaja;
use App\Models\User;

class ReabrirCajaDelDia
{
    /**
     * Reabre un día cerrado para corregirlo. Se descartan las cifras del arqueo, porque se vuelven
     * a calcular al cerrar de nuevo, y queda constancia de quién lo reabrió. Los gastos se conservan.
     */
    public function ejecutar(CierreCaja $cierre, User $usuario): CierreCaja
    {
        abort_unless($cierre->estaCerrado(), 403, 'El día no está cerrado.');
        abort_if($cierre->consignacion()->exists(), 403, 'El día ya tiene una consignación: elimínela antes de reabrirlo.');

        $cierre->update([
            'estado' => EstadoCierre::Abierto,
            'valor_base_caja' => null,
            'valor_efectivo_ventas' => null,
            'valor_pago_gestores' => null,
            'valor_gastos' => null,
            'valor_efectivo_esperado' => null,
            'valor_efectivo_contado' => null,
            'valor_diferencia_efectivo' => null,
            'valor_ventas_datafono' => null,
            'valor_reportado_datafono' => null,
            'valor_diferencia_datafono' => null,
            'valor_ventas_qr' => null,
            'valor_reportado_qr' => null,
            'valor_diferencia_qr' => null,
            'valor_a_consignar' => null,
            'justificacion' => null,
            'cerrado_por' => null,
            'cerrado_en' => null,
            'reabierto_por' => $usuario->id,
            'reabierto_en' => now(),
        ]);

        return $cierre;
    }
}
