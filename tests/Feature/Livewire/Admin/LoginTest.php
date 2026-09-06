<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('admin-login:127.0.0.1');
    }

    public function test_login_exitoso_autentica_y_redirige(): void
    {
        $admin = User::factory()->create(['password' => Hash::make('password-valido')]);

        Livewire::test(Login::class)
            ->set('email', $admin->email)
            ->set('password', 'password-valido')
            ->call('autenticar');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_credenciales_invalidas_no_autentican(): void
    {
        User::factory()->create(['email' => 'admin@mallasarica.cl', 'password' => Hash::make('correcta')]);

        Livewire::test(Login::class)
            ->set('email', 'admin@mallasarica.cl')
            ->set('password', 'incorrecta')
            ->call('autenticar')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_throttle_bloquea_tras_exceder_el_maximo_de_intentos(): void
    {
        User::factory()->create(['email' => 'admin@mallasarica.cl', 'password' => Hash::make('correcta')]);

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->set('email', 'admin@mallasarica.cl')
                ->set('password', 'incorrecta')
                ->call('autenticar');
        }

        Livewire::test(Login::class)
            ->set('email', 'admin@mallasarica.cl')
            ->set('password', 'incorrecta')
            ->call('autenticar')
            ->assertHasErrors('throttle');

        $this->assertGuest();
    }
}
