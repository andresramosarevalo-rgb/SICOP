<?php

namespace App\Http\Controllers\Nomina;

use App\Actions\Nomina\EnviarRecibosPeriodo;
use App\Enums\TipoConcepto;
use App\Http\Controllers\Controller;
use App\Models\DetalleReciboNomina;
use App\Models\ReciboNomina;
use Illuminate\Http\RedirectResponse;
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

    /**
     * Vuelve a enviar por correo un recibo de un periodo cerrado.
     */
    public function reenviar(ReciboNomina $recibo, EnviarRecibosPeriodo $enviarRecibos): RedirectResponse
    {
        $enviarRecibos->reenviar($recibo);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Recibo reenviado.']);

        return to_route('nomina.periodos.show', $recibo->periodo_nomina_id);
    }
}
