<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Clave-valor genérico para configuración técnica sensible (credenciales de
 * integraciones) que el super_admin gestiona desde el panel («Ajustes»), en
 * vez de .env — así queda auditable y editable sin acceso al servidor.
 * `valor` se cifra con el cast `encrypted` (usa APP_KEY): nunca queda en
 * texto plano en la BD ni en un dump/backup sin la app_key.
 */
class Configuracion extends Model
{
    protected $table = 'configuraciones';

    protected $fillable = [
        'key',
        'valor',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'encrypted',
        ];
    }

    public static function obtener(string $key): ?string
    {
        return static::where('key', $key)->first()?->valor;
    }

    public static function guardar(string $key, ?string $valor): void
    {
        static::updateOrCreate(['key' => $key], ['valor' => $valor]);
    }
}
