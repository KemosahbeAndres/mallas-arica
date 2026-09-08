<?php

namespace App\Console\Commands;

use App\Jobs\NotificarNuevoCliente;
use App\Models\Cliente;
use Illuminate\Console\Command;

class ReintentarNotificaciones extends Command
{
    protected $signature = 'app:reintentar-notificaciones';

    protected $description = 'Re-despacha el aviso por correo de los clientes del sitio que quedaron sin notificar en las últimas 72 horas';

    public function handle(): int
    {
        $clientes = Cliente::query()
            ->whereNull('notificado_at')
            ->where('created_at', '>=', now()->subHours(72))
            ->get();

        foreach ($clientes as $cliente) {
            NotificarNuevoCliente::dispatch($cliente);
        }

        $this->info("Re-despachadas {$clientes->count()} notificaciones pendientes.");

        return self::SUCCESS;
    }
}
