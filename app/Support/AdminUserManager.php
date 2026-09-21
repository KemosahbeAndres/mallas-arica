<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class AdminUserManager
{
    /**
     * Único punto de alta fuera del CRUD del panel (`/admin/usuarios`). El
     * usuario resultante siempre es el Super Administrador (hay uno solo).
     */
    public static function crear(string $email, string $password, string $name = 'Admin'): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password), 'rol' => User::ROL_SUPER_ADMIN],
        );
    }
}
