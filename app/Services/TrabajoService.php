<?php

namespace App\Services;

use App\Models\Evento;
use App\Models\Trabajo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Operaciones sobre una OT (§11 ter, §11 octies).
 */
class TrabajoService
{
    /**
     * Cambia el estado de la OT. Al pasar a `ejecutada`, fija `finalizado_at`
     * (si no lo tiene ya); al salir de `ejecutada`, lo limpia.
     */
    public function cambiarEstado(Trabajo $trabajo, string $estado, ?string $fecha = null): void
    {
        if (! in_array($estado, Trabajo::ESTADOS, true)) {
            return;
        }

        $datos = ['estado' => $estado];

        if ($estado === 'ejecutada') {
            $datos['finalizado_at'] = $fecha
                ? CarbonImmutable::createFromFormat('Y-m-d', $fecha)->startOfDay()
                : ($trabajo->finalizado_at ?? CarbonImmutable::now());
        } elseif ($trabajo->estado === 'ejecutada') {
            $datos['finalizado_at'] = null;
        }

        $trabajo->update($datos);
    }

    /**
     * Crea (o actualiza) el evento de agenda asociado a la OT. La OT apunta al
     * evento vía `trabajos.evento_id`, no al revés (CLAUDE.md §11 ter).
     */
    public function agendar(Trabajo $trabajo, string $fecha, ?string $hora, ?string $notas = null): Evento
    {
        return DB::transaction(function () use ($trabajo, $fecha, $hora, $notas) {
            $todoElDia = blank($hora);
            $inicio = $todoElDia
                ? CarbonImmutable::createFromFormat('Y-m-d', $fecha)->startOfDay()
                : CarbonImmutable::createFromFormat('Y-m-d H:i', "{$fecha} {$hora}");

            $evento = $trabajo->evento ?? new Evento;
            $evento->fill([
                'titulo' => $trabajo->titulo,
                'descripcion' => $trabajo->descripcion,
                'tipo' => 'terreno',
                'estado' => 'agendado',
                'inicio' => $inicio,
                'todo_el_dia' => $todoElDia,
                'cliente_id' => $trabajo->cliente_id,
                'ubicacion' => $trabajo->clienteDireccion?->direccion,
                'notas' => $notas,
            ])->save();

            $trabajo->update(['evento_id' => $evento->id]);

            return $evento;
        });
    }

    public function desagendar(Trabajo $trabajo): void
    {
        $evento = $trabajo->evento;
        $trabajo->update(['evento_id' => null]);
        $evento?->delete();
    }
}
