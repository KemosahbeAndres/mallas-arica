<?php

namespace App\Services;

use App\Models\User;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event;
use RuntimeException;

/**
 * Sincronización de eventos con Google Calendar para un usuario que ya
 * conectó su cuenta (User::tieneGoogleCalendarConectado()). Las credenciales
 * de la app (client_id/secret) vienen de GoogleOAuthConfig — igual que
 * GoogleLoginController — no de config('services.google.*'), que en este
 * proyecto no se puebla de forma estática.
 *
 * Sigue el patrón de servicio dedicado del proyecto (ver
 * CotizacionCalculatorService): construir con el usuario dueño de los
 * tokens y dejar que el propio servicio resuelva el refresh si hace falta.
 */
class GoogleCalendarService
{
    private GoogleClient $client;

    public function __construct(private User $user, GoogleOAuthConfig $config)
    {
        if (! $user->tieneGoogleCalendarConectado()) {
            throw new RuntimeException("El usuario #{$user->id} no tiene Google Calendar conectado.");
        }

        $this->client = new GoogleClient;
        $this->client->setClientId($config->clientId());
        $this->client->setClientSecret($config->clientSecret());
        $this->client->setAccessToken([
            'access_token' => $user->google_token,
            'refresh_token' => $user->google_refresh_token,
            'expires_in' => $user->google_token_expires_at
                ? now()->diffInSeconds($user->google_token_expires_at, absolute: false)
                : 0,
        ]);

        $this->refreshTokenIfNeeded();
    }

    private function refreshTokenIfNeeded(): void
    {
        if (! $this->client->isAccessTokenExpired()) {
            return;
        }

        $nuevoToken = $this->client->fetchAccessTokenWithRefreshToken(
            $this->user->google_refresh_token
        );

        if (isset($nuevoToken['error'])) {
            throw new RuntimeException(
                "No se pudo refrescar el token de Google del usuario #{$this->user->id}: {$nuevoToken['error']}"
            );
        }

        $this->user->update([
            'google_token' => $nuevoToken['access_token'],
            'google_token_expires_at' => now()->addSeconds($nuevoToken['expires_in']),
        ]);
    }

    public function createEvent(array $data): Event
    {
        $service = new GoogleCalendar($this->client);

        $event = new Event([
            'summary' => $data['summary'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'start' => $this->fechaGoogle($data['start'], $data['todo_el_dia'] ?? false),
            'end' => $this->fechaGoogle($data['end'], $data['todo_el_dia'] ?? false),
        ]);

        return $service->events->insert('primary', $event);
    }

    public function updateEvent(string $googleEventId, array $data): Event
    {
        $service = new GoogleCalendar($this->client);

        $event = new Event([
            'summary' => $data['summary'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'start' => $this->fechaGoogle($data['start'], $data['todo_el_dia'] ?? false),
            'end' => $this->fechaGoogle($data['end'], $data['todo_el_dia'] ?? false),
        ]);

        return $service->events->update('primary', $googleEventId, $event);
    }

    public function deleteEvent(string $googleEventId): void
    {
        $service = new GoogleCalendar($this->client);

        $service->events->delete('primary', $googleEventId);
    }

    /**
     * @return Event[]
     */
    public function listEvents(int $maxResults = 20): array
    {
        $service = new GoogleCalendar($this->client);

        $resultados = $service->events->listEvents('primary', [
            'maxResults' => $maxResults,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => now()->toRfc3339String(),
        ]);

        return $resultados->getItems();
    }

    /** @return array{date: string}|array{dateTime: string, timeZone: string} */
    private function fechaGoogle(string $fecha, bool $todoElDia): array
    {
        return $todoElDia
            ? ['date' => substr($fecha, 0, 10)]
            : ['dateTime' => $fecha, 'timeZone' => 'America/Santiago'];
    }
}
