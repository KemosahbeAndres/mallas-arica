<?php

namespace App\Mail;

use App\Models\Cotizacion;
use App\Services\CotizacionPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CopiaCotizacionCliente extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Cotizacion $cotizacion,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Tu solicitud N° {$this->cotizacion->numero} — Mallas Arica Jacob",
            replyTo: [config('mail.admin_address')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.copia-cotizacion-cliente',
            with: [
                'cotizacion' => $this->cotizacion,
                'tieneItems' => $this->cotizacion->items()->exists(),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->cotizacion->items()->exists()) {
            return [];
        }

        $pdfService = app(CotizacionPdfService::class);

        return [
            Attachment::fromData(fn () => $pdfService->render($this->cotizacion), $pdfService->nombreArchivo($this->cotizacion))
                ->withMime('application/pdf'),
        ];
    }
}
