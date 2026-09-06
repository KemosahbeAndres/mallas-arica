<?php

namespace App\Mail;

use App\Models\Cotizacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NuevaCotizacionAdmin extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Cotizacion $cotizacion,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Cotización N° {$this->cotizacion->numero} — {$this->cotizacion->nombre} ({$this->cotizacion->telefono})",
            replyTo: $this->cotizacion->email ? [$this->cotizacion->email] : [],
        );
    }

    public function content(): Content
    {
        $this->cotizacion->loadMissing('items.tipoEspacio', 'items.tipoMalla', 'items.tramoAltura');

        return new Content(
            view: 'emails.nueva-cotizacion-admin',
            with: [
                'cotizacion' => $this->cotizacion,
            ],
        );
    }
}
