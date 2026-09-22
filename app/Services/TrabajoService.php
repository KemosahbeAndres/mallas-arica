<?php

namespace App\Services;

use App\Exceptions\TrabajoTransicionInvalidaException;
use App\Models\Evento;
use App\Models\Trabajo;
use App\Models\User;
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
     *
     * Si $actor es un colaborador, solo puede mover la OT entre `en_curso` y
     * `ejecutada`, y solo si está asignado a ella. El mínimo de fotos exigido
     * (Trabajo::minimoFotos()) se exige para cualquier actor al pasar a
     * `ejecutada` — es una regla de calidad del dato, no de permisos.
     */
    public function cambiarEstado(Trabajo $trabajo, string $estado, ?string $fecha = null, ?User $actor = null): void
    {
        if (! in_array($estado, Trabajo::ESTADOS, true)) {
            return;
        }

        if ($actor && $actor->esColaborador()) {
            if (! in_array($estado, ['en_curso', 'ejecutada'], true)) {
                throw new TrabajoTransicionInvalidaException(
                    'Solo puedes marcar el trabajo como "en curso" o "ejecutada".'
                );
            }

            if (! $trabajo->colaboradores->contains('id', $actor->id)) {
                throw new TrabajoTransicionInvalidaException('No tienes asignado este trabajo.');
            }
        }

        if ($estado === 'ejecutada' && ! $trabajo->cumpleMinimoFotos()) {
            throw new TrabajoTransicionInvalidaException(
                "Faltan fotos: se requieren {$trabajo->minimoFotos()} y hay {$trabajo->fotos()->count()}."
            );
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

    /**
     * Asigna/reasigna los colaboradores que ejecutarán la OT. Reemplaza la
     * asignación anterior (no acumula). Solo debe invocarse desde puntos ya
     * autorizados (Agenda) — no revalida el rol del llamador aquí.
     *
     * @param  array<int, int>  $userIds
     */
    public function asignarColaboradores(Trabajo $trabajo, array $userIds): void
    {
        $trabajo->colaboradores()->sync($userIds);
    }
}
