<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class AdminUserManager
{
    public static function crear(string $email, string $password, string $name = 'Admin'): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password)],
        );
    }
}
