<?php

namespace App\Mail;

use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso al dueño de que llegó un contacto nuevo desde el formulario del sitio
 * (Sprint 12: el formulario público crea un Cliente, ya no una cotización).
 */
class NuevoClienteAdmin extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Cliente $cliente,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nuevo contacto desde el sitio — {$this->cliente->nombre} ({$this->cliente->telefono})",
            replyTo: $this->cliente->email ? [$this->cliente->email] : [],
        );
    }

    public function content(): Content
    {
        $this->cliente->loadMissing('direcciones');

        return new Content(
            view: 'emails.nuevo-cliente-admin',
            with: ['cliente' => $this->cliente],
        );
    }
}
