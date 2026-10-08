<?php

namespace App\Mail;

use App\Enums\TipoConcepto;
use App\Models\DetalleReciboNomina;
use App\Models\ReciboNomina;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Recibo de pago de nómina que se envía por correo al empleado.
 */
class ReciboNominaMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public ReciboNomina $recibo) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $periodo = $this->recibo->periodo;

        return new Envelope(
            subject: "Recibo de pago de nómina {$periodo->fecha_inicio->toDateString()} a {$periodo->fecha_fin->toDateString()}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $this->recibo->loadMissing(['periodo', 'empleado', 'detalles']);
        [$devengos, $deducciones] = $this->recibo->detalles
            ->partition(fn (DetalleReciboNomina $detalle) => $detalle->tipo === TipoConcepto::Devengo);

        return new Content(
            markdown: 'mail.nomina.recibo',
            with: [
                'empleado' => $this->recibo->empleado,
                'periodo' => $this->recibo->periodo,
                'devengos' => $devengos,
                'deducciones' => $deducciones,
            ],
        );
    }
}
