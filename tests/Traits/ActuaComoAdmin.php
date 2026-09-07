<?php

namespace Tests\Traits;

use App\Models\User;

trait ActuaComoAdmin
{
    protected function actuarComoAdmin(): User
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        return $admin;
    }
}
