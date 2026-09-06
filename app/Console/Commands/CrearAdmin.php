<?php

namespace App\Console\Commands;

use App\Support\AdminUserManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CrearAdmin extends Command
{
    protected $signature = 'app:crear-admin
        {--email= : Correo del admin}
        {--password= : Contraseña del admin}
        {--name=Admin : Nombre del admin}
        {--from-env : Lee ADMIN_EMAIL/ADMIN_PASSWORD/ADMIN_NAME del entorno en vez de preguntar}';

    protected $description = 'Crea o actualiza el usuario admin del panel. Sin UI de registro pública.';

    public function handle(): int
    {
        if ($this->option('from-env')) {
            $email = env('ADMIN_EMAIL');
            $password = env('ADMIN_PASSWORD');
            $name = env('ADMIN_NAME', 'Admin');

            if (! $email || ! $password) {
                $this->error('ADMIN_EMAIL y ADMIN_PASSWORD deben estar definidos en el entorno.');

                return self::FAILURE;
            }
        } else {
            $email = $this->option('email') ?: $this->ask('Correo del admin');
            $password = $this->option('password') ?: $this->secret('Contraseña del admin');
            $name = $this->option('name');
        }

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email'], 'password' => ['required', 'string', 'min:8']],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $admin = AdminUserManager::crear($email, $password, $name);

        $this->info("Admin listo: {$admin->email}");

        return self::SUCCESS;
    }
}
