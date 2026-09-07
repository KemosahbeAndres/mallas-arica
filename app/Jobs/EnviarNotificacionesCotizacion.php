<?php

namespace App\Jobs;

use App\Mail\CopiaCotizacionCliente;
use App\Mail\NuevaCotizacionAdmin;
use App\Models\Cotizacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EnviarNotificacionesCotizacion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public function __construct(
        public Cotizacion $cotizacion,
    ) {}

    public function handle(): void
    {
        if ($this->cotizacion->notificado_at !== null) {
            return;
        }

        Mail::send(new NuevaCotizacionAdmin($this->cotizacion));

        if ($this->cotizacion->email) {
            Mail::send(new CopiaCotizacionCliente($this->cotizacion));
        }

        $this->cotizacion->forceFill(['notificado_at' => now()])->save();
    }

    public function failed(Throwable $exception): void
    {
        Log::error('No se pudo notificar la cotización', [
            'cotizacion_id' => $this->cotizacion->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
