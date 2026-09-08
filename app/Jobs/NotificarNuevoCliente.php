<?php

namespace App\Jobs;

use App\Mail\NuevoClienteAdmin;
use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Avisa al dueño de un contacto nuevo llegado por el formulario del sitio.
 * Idempotente: no reenvía si el cliente ya tiene `notificado_at`.
 */
class NotificarNuevoCliente implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public function __construct(
        public Cliente $cliente,
    ) {}

    public function handle(): void
    {
        if ($this->cliente->notificado_at !== null) {
            return;
        }

        Mail::send(new NuevoClienteAdmin($this->cliente));

        $this->cliente->forceFill(['notificado_at' => now()])->save();
    }

    public function failed(Throwable $exception): void
    {
        Log::error('No se pudo notificar el nuevo cliente', [
            'cliente_id' => $this->cliente->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
