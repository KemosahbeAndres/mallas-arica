<?php

namespace App\Console\Commands;

use App\Jobs\EnviarNotificacionesCotizacion;
use App\Models\Cotizacion;
use Illuminate\Console\Command;

class ReintentarNotificaciones extends Command
{
    protected $signature = 'app:reintentar-notificaciones';

    protected $description = 'Re-despacha el aviso por correo de las cotizaciones que quedaron sin notificar en las últimas 72 horas';

    public function handle(): int
    {
        $cotizaciones = Cotizacion::query()
            ->whereNull('notificado_at')
            ->where('created_at', '>=', now()->subHours(72))
            ->get();

        foreach ($cotizaciones as $cotizacion) {
            EnviarNotificacionesCotizacion::dispatch($cotizacion);
        }

        $this->info("Re-despachadas {$cotizaciones->count()} notificaciones pendientes.");

        return self::SUCCESS;
    }
}
