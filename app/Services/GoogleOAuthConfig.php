<?php

namespace App\Services;

use App\Models\Configuracion;
use InvalidArgumentException;

/**
 * Credenciales OAuth de Google para el login del panel admin, gestionadas
 * desde /admin/ajustes/google (super_admin) en vez de .env — el super_admin
 * sube el JSON tal cual lo entrega Google Cloud Console. El client_secret se
 * guarda cifrado (Configuracion::valor usa el cast `encrypted`).
 */
class GoogleOAuthConfig
{
    private const CLIENT_ID = 'google_oauth.client_id';

    private const CLIENT_SECRET = 'google_oauth.client_secret';

    public function clientId(): ?string
    {
        return Configuracion::obtener(self::CLIENT_ID);
    }

    public function clientSecret(): ?string
    {
        return Configuracion::obtener(self::CLIENT_SECRET);
    }

    public function configurado(): bool
    {
        return filled($this->clientId()) && filled($this->clientSecret());
    }

    /** Últimos 4 caracteres del client_secret, para mostrar sin exponerlo. */
    public function clientSecretParcial(): ?string
    {
        $secret = $this->clientSecret();

        return $secret ? str_repeat('•', 12).substr($secret, -4) : null;
    }

    /**
     * Guarda las credenciales a partir del JSON que entrega Google Cloud
     * Console para un cliente OAuth de tipo "Web application":
     * {"web": {"client_id": "...", "client_secret": "...", ...}}
     */
    public function guardarDesdeJson(string $json): void
    {
        $datos = json_decode($json, true);
        $web = $datos['web'] ?? $datos['installed'] ?? null;

        if (! is_array($web) || empty($web['client_id']) || empty($web['client_secret'])) {
            throw new InvalidArgumentException(
                'El archivo no tiene el formato esperado (falta client_id o client_secret bajo "web").'
            );
        }

        Configuracion::guardar(self::CLIENT_ID, $web['client_id']);
        Configuracion::guardar(self::CLIENT_SECRET, $web['client_secret']);
    }

    public function eliminar(): void
    {
        Configuracion::guardar(self::CLIENT_ID, null);
        Configuracion::guardar(self::CLIENT_SECRET, null);
    }
}
