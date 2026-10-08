<?php

namespace App\Actions\Nomina;

use App\Mail\ReciboNominaMail;
use App\Models\PeriodoNomina;
use App\Models\ReciboNomina;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Pone en la cola de correo los recibos de un periodo cerrado y registra la fecha de envío.
 */
final class EnviarRecibosPeriodo
{
    /**
     * Envía los recibos que aún no se han enviado.
     *
     * @return int Cantidad de recibos enviados.
     *
     * @throws ValidationException si el periodo no está cerrado.
     */
    public function ejecutar(PeriodoNomina $periodo): int
    {
        $this->asegurarCerrado($periodo);

        $recibos = $periodo->recibos()->whereNull('fecha_envio')->with(['empleado', 'periodo'])->get();
        $recibos->each(fn (ReciboNomina $recibo) => $this->enviar($recibo));

        return $recibos->count();
    }

    /**
     * Envía (o reenvía) un recibo, aunque ya se haya enviado antes.
     *
     * @throws ValidationException si el periodo del recibo no está cerrado.
     */
    public function reenviar(ReciboNomina $recibo): void
    {
        $this->asegurarCerrado($recibo->periodo);
        $this->enviar($recibo->loadMissing('empleado'));
    }

    private function enviar(ReciboNomina $recibo): void
    {
        Mail::to($recibo->empleado->email, "{$recibo->empleado->nombres} {$recibo->empleado->apellidos}")
            ->queue(new ReciboNominaMail($recibo));

        $recibo->forceFill(['fecha_envio' => now()])->save();
    }

    private function asegurarCerrado(PeriodoNomina $periodo): void
    {
        if (! $periodo->estaCerrado()) {
            throw ValidationException::withMessages(['periodo' => 'Solo se pueden enviar los recibos de un periodo cerrado.']);
        }
    }
}
