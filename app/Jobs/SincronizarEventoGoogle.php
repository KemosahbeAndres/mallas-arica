<?php

namespace App\Jobs;

use App\Models\Evento;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\GoogleOAuthConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Refleja un `Evento` de esta plataforma (fuente de verdad de la agenda de
 * trabajo, CLAUDE.md §11 quinquies) como copia espejo en el Google Calendar
 * personal de cada usuario asignado (`evento.usuarios`) que tenga Calendar
 * conectado (User::tieneGoogleCalendarConectado()). Un usuario sin Calendar
 * conectado simplemente no recibe copia — no es un error.
 *
 * Idempotente: si el usuario ya tenía un espejo (`google_event_ids[user_id]`),
 * lo actualiza en vez de duplicarlo. Si un usuario fue quitado de `usuarios`
 * desde la última sincronización, su espejo (si lo había) se borra.
 */
class SincronizarEventoGoogle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public function __construct(public Evento $evento) {}

    public function handle(GoogleOAuthConfig $config): void
    {
        if (! $config->configurado()) {
            return;
        }

        if ($this->evento->estado === 'cancelado') {
            $this->borrarTodosLosEspejos($config);

            return;
        }

        $usuarios = $this->evento->usuarios()->get();
        $idsUsuariosAsignados = $usuarios->pluck('id')->all();
        $mapaEventos = $this->evento->google_event_ids ?? [];

        foreach ($usuarios as $usuario) {
            if (! $usuario->tieneGoogleCalendarConectado()) {
                continue;
            }

            $mapaEventos[$usuario->id] = $this->sincronizarParaUsuario($usuario, $config, $mapaEventos[$usuario->id] ?? null);
        }

        // Usuarios que tenían espejo pero ya no están asignados al evento.
        foreach (array_keys($mapaEventos) as $userId) {
            if (! in_array((int) $userId, $idsUsuariosAsignados, true)) {
                $this->borrarEspejo((int) $userId, $mapaEventos[$userId], $config);
                unset($mapaEventos[$userId]);
            }
        }

        $this->evento->forceFill(['google_event_ids' => $mapaEventos])->saveQuietly();
    }

    private function sincronizarParaUsuario(User $usuario, GoogleOAuthConfig $config, ?string $googleEventIdExistente): ?string
    {
        try {
            $servicio = new GoogleCalendarService($usuario, $config);
            $datos = $this->datosEvento();

            if ($googleEventIdExistente) {
                return $servicio->updateEvent($googleEventIdExistente, $datos)->getId();
            }

            return $servicio->createEvent($datos)->getId();
        } catch (Throwable $e) {
            Log::warning('No se pudo sincronizar el evento con Google Calendar', [
                'evento_id' => $this->evento->id,
                'user_id' => $usuario->id,
                'error' => $e->getMessage(),
            ]);

            return $googleEventIdExistente;
        }
    }

    private function borrarTodosLosEspejos(GoogleOAuthConfig $config): void
    {
        $mapaEventos = $this->evento->google_event_ids ?? [];

        foreach ($mapaEventos as $userId => $googleEventId) {
            $this->borrarEspejo((int) $userId, $googleEventId, $config);
        }

        $this->evento->forceFill(['google_event_ids' => null])->saveQuietly();
    }

    private function borrarEspejo(int $userId, ?string $googleEventId, GoogleOAuthConfig $config): void
    {
        if (! $googleEventId) {
            return;
        }

        $usuario = User::find($userId);

        if (! $usuario || ! $usuario->tieneGoogleCalendarConectado()) {
            return;
        }

        try {
            (new GoogleCalendarService($usuario, $config))->deleteEvent($googleEventId);
        } catch (Throwable $e) {
            Log::warning('No se pudo borrar el evento espejo en Google Calendar', [
                'evento_id' => $this->evento->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** @return array{summary: string, description: ?string, location: ?string, start: string, end: string, todo_el_dia: bool} */
    private function datosEvento(): array
    {
        $inicio = $this->evento->inicio;
        $fin = $this->evento->fin ?? $inicio->clone()->addHour();

        return [
            'summary' => $this->evento->titulo,
            'description' => $this->evento->descripcion,
            'location' => $this->evento->ubicacion,
            'start' => $this->evento->todo_el_dia ? $inicio->toDateString() : $inicio->toRfc3339String(),
            'end' => $this->evento->todo_el_dia ? $inicio->toDateString() : $fin->toRfc3339String(),
            'todo_el_dia' => $this->evento->todo_el_dia,
        ];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Falló la sincronización del evento con Google Calendar', [
            'evento_id' => $this->evento->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
