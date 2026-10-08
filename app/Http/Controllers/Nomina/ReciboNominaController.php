<?php

namespace App\Http\Controllers\Nomina;

use App\Enums\TipoConcepto;
use App\Http\Controllers\Controller;
use App\Models\DetalleReciboNomina;
use App\Models\ReciboNomina;
use Inertia\Inertia;
use Inertia\Response;

class ReciboNominaController extends Controller
{
    /**
     * Muestra el recibo de pago con el desglose de devengos y deducciones.
     */
    public function show(ReciboNomina $recibo): Response
    {
        $recibo->load(['periodo', 'empleado.area:id,nombre', 'detalles']);
        [$devengos, $deducciones] = $recibo->detalles
            ->partition(fn (DetalleReciboNomina $detalle) => $detalle->tipo === TipoConcepto::Devengo);

        return Inertia::render('Nomina/Recibos/Show', [
            'recibo' => $recibo->only([
                'id', 'valor_salario_base', 'dias_liquidados', 'valor_total_devengado',
                'valor_total_deducciones', 'valor_neto', 'fecha_envio',
            ]),
            'periodo' => $recibo->periodo->only(['id', 'periodicidad_pago', 'fecha_inicio', 'fecha_fin', 'estado']),
            'empleado' => [
                ...$recibo->empleado->only(['nombres', 'apellidos', 'numero_documento', 'cargo', 'email']),
                'tipo_documento' => $recibo->empleado->tipo_documento->value,
                'area' => $recibo->empleado->area->nombre,
            ],
            'devengos' => $devengos->values(),
            'deducciones' => $deducciones->values(),
        ]);
    }
}
