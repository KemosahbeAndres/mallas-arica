<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Configuracion::guardar('google_oauth.client_id', 'test-client-id');
        Configuracion::guardar('google_oauth.client_secret', 'test-client-secret');
    }

    private function mockCuentaGoogle(string $email, string $googleId = 'google-123'): void
    {
        $cuenta = Mockery::mock(SocialiteUser::class);
        $cuenta->shouldReceive('getEmail')->andReturn($email);
        $cuenta->shouldReceive('getId')->andReturn($googleId);

        $driver = Mockery::mock();
        $driver->shouldReceive('redirectUrl')->andReturnSelf();
        $driver->shouldReceive('user')->andReturn($cuenta);

        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);
    }

    public function test_usuario_existente_puede_entrar_con_google(): void
    {
        // En local (config('app.domain') === 'localhost' en tests), el callback
        // no autentica directo: redirige a un token de un solo uso que se
        // consume ya en el host admin.localhost (ver GoogleLoginController).
        $usuario = User::factory()->create(['email' => 'coli@mallasarica.cl', 'email_google' => 'coli@gmail.com']);
        $this->mockCuentaGoogle('coli@gmail.com', 'google-abc');

        $respuesta = $this->getAdmin('/login/google/callback');
        $respuesta->assertRedirect();
        $this->assertStringContainsString('/login/google/consumir/', $respuesta->headers->get('Location'));

        $this->assertGuest();
        $this->assertSame('google-abc', $usuario->refresh()->google_id);

        $token = Str::afterLast($respuesta->headers->get('Location'), '/');
        $this->getAdmin("/login/google/consumir/{$token}")->assertRedirect(route('admin.resumen'));

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_token_de_consumo_es_de_un_solo_uso(): void
    {
        User::factory()->create(['email' => 'coli@mallasarica.cl', 'email_google' => 'coli@gmail.com']);
        $this->mockCuentaGoogle('coli@gmail.com');

        $respuesta = $this->getAdmin('/login/google/callback');
        $token = Str::afterLast($respuesta->headers->get('Location'), '/');

        $this->getAdmin("/login/google/consumir/{$token}");
        Auth::logout();

        $this->getAdmin("/login/google/consumir/{$token}")
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_email_no_registrado_no_crea_usuario_ni_autentica(): void
    {
        $this->mockCuentaGoogle('desconocido@gmail.com');

        $this->getAdmin('/login/google/callback')
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email_google' => 'desconocido@gmail.com']);
    }

    public function test_no_sobreescribe_google_id_ya_vinculado(): void
    {
        $usuario = User::factory()->create([
            'email' => 'coli@mallasarica.cl',
            'email_google' => 'coli@gmail.com',
            'google_id' => 'original',
        ]);
        $this->mockCuentaGoogle('coli@gmail.com', 'otro-id');

        $this->getAdmin('/login/google/callback');

        $this->assertSame('original', $usuario->refresh()->google_id);
    }

    public function test_sin_credenciales_configuradas_devuelve_503(): void
    {
        Configuracion::guardar('google_oauth.client_id', null);
        Configuracion::guardar('google_oauth.client_secret', null);

        $this->getAdmin('/login/google')->assertStatus(503);
    }
}
