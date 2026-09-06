<?php

namespace Database\Seeders;

use App\Support\AdminUserManager;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Crea (o actualiza) el usuario admin desde variables de entorno.
     * Sin UI de registro pública: este es el único punto de alta fuera
     * del comando `app:crear-admin`.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            throw new RuntimeException('ADMIN_EMAIL y ADMIN_PASSWORD deben estar definidos en .env para sembrar el usuario admin.');
        }

        AdminUserManager::crear($email, $password, env('ADMIN_NAME', 'Admin'));
    }
}
