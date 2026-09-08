<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\Trabajo;
use Illuminate\Support\Facades\DB;

/**
 * Cambios de estado de una cotización y sus efectos.
 *
 * Al pasar a `aceptada` se crea la OT (tabla `trabajos`) con la doble relación
 * cliente_id + cotizacion_id (CLAUDE.md §11 ter). Idempotente: si la cotización
 * ya tiene OT, no crea otra.
 */
class CotizacionEstadoService
{
    public function cambiar(Cotizacion $cotizacion, string $nuevoEstado): void
    {
        if (! in_array($nuevoEstado, Cotizacion::ESTADOS, true)) {
            return;
        }

        DB::transaction(function () use ($cotizacion, $nuevoEstado) {
            $cotizacion->update(['estado' => $nuevoEstado]);

            if ($nuevoEstado === 'aceptada') {
                $this->crearOtSiFalta($cotizacion);
            }
        });
    }

    private function crearOtSiFalta(Cotizacion $cotizacion): void
    {
        if ($cotizacion->trabajo()->exists()) {
            return;
        }

        $cliente = $cotizacion->cliente;
        $nombreCliente = $cliente?->nombre ?? $cotizacion->nombre ?? 'cliente';

        Trabajo::create([
            'cliente_id' => $cotizacion->cliente_id,
            'cotizacion_id' => $cotizacion->id,
            'titulo' => "Instalación — {$nombreCliente} (cotización {$cotizacion->folio})",
            'descripcion' => $cotizacion->items->pluck('descripcion')->filter()->implode("\n"),
            'estado' => 'pendiente',
        ]);
    }
}
