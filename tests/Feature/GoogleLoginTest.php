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

    private function mockCuentaGoogle(string $email, string $googleId = 'google-123', ?string $refreshToken = 'refresh-token-test'): void
    {
        $cuenta = Mockery::mock(SocialiteUser::class);
        $cuenta->shouldReceive('getEmail')->andReturn($email);
        $cuenta->shouldReceive('getId')->andReturn($googleId);
        $cuenta->token = 'access-token-test';
        $cuenta->refreshToken = $refreshToken;
        $cuenta->expiresIn = 3600;

        $driver = Mockery::mock();
        $driver->shouldReceive('scopes')->andReturnSelf();
        $driver->shouldReceive('with')->andReturnSelf();
        $driver->shouldReceive('user')->andReturn($cuenta);
        $driver->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);
    }

    public function test_usuario_existente_puede_entrar_con_google(): void
    {
        // En local (config('app.domain') === 'localhost' en tests), el
        // redirect() y el callback() ocurren en "localhost" sin subdominio
        // (Google no acepta admin.localhost) — el callback no autentica
        // directo: redirige a un token de un solo uso que se consume ya en
        // el host admin.localhost (ver GoogleLoginController).
        $usuario = User::factory()->create(['email' => 'coli@mallasarica.cl', 'email_google' => 'coli@gmail.com']);
        $this->mockCuentaGoogle('coli@gmail.com', 'google-abc');

        $respuesta = $this->getPublico('/auth/google/callback');
        $respuesta->assertRedirect();
        $this->assertStringContainsString('/auth/google/consumir/', $respuesta->headers->get('Location'));

        $this->assertGuest();
        $this->assertSame('google-abc', $usuario->refresh()->google_id);

        $token = Str::afterLast($respuesta->headers->get('Location'), '/');
        $this->getAdmin("/auth/google/consumir/{$token}")->assertRedirect(route('admin.resumen'));

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_token_de_consumo_es_de_un_solo_uso(): void
    {
        User::factory()->create(['email' => 'coli@mallasarica.cl', 'email_google' => 'coli@gmail.com']);
        $this->mockCuentaGoogle('coli@gmail.com');

        $respuesta = $this->getPublico('/auth/google/callback');
        $token = Str::afterLast($respuesta->headers->get('Location'), '/');

        $this->getAdmin("/auth/google/consumir/{$token}");
        Auth::logout();

        $this->getAdmin("/auth/google/consumir/{$token}")
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_email_no_registrado_no_crea_usuario_ni_autentica(): void
    {
        $this->mockCuentaGoogle('desconocido@gmail.com');

        $this->getPublico('/auth/google/callback')
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

        $this->getPublico('/auth/google/callback');

        $this->assertSame('original', $usuario->refresh()->google_id);
    }

    public function test_sin_credenciales_configuradas_devuelve_503(): void
    {
        Configuracion::guardar('google_oauth.client_id', null);
        Configuracion::guardar('google_oauth.client_secret', null);

        $this->getPublico('/auth/google')->assertStatus(503);
    }

    public function test_guarda_tokens_de_calendar_al_entrar(): void
    {
        $usuario = User::factory()->create(['email' => 'coli@mallasarica.cl', 'email_google' => 'coli@gmail.com']);
        $this->mockCuentaGoogle('coli@gmail.com', refreshToken: 'refresh-nuevo');

        $this->getPublico('/auth/google/callback');

        $usuario->refresh();
        $this->assertSame('access-token-test', $usuario->google_token);
        $this->assertSame('refresh-nuevo', $usuario->google_refresh_token);
        $this->assertNotNull($usuario->google_token_expires_at);
    }

    public function test_sin_refresh_token_y_sin_uno_guardado_reintenta_forzando_consentimiento(): void
    {
        User::factory()->create(['email' => 'coli@mallasarica.cl', 'email_google' => 'coli@gmail.com']);
        // Google no reenvía refresh_token si el usuario ya había autorizado el
        // scope antes sin forzar prompt=consent — el controller debe detectar
        // que no hay uno guardado y reintentar una vez, no fallar ni loopear.
        $this->mockCuentaGoogle('coli@gmail.com', refreshToken: null);

        $respuesta = $this->getPublico('/auth/google/callback');

        $respuesta->assertRedirect('https://accounts.google.com/o/oauth2/auth');
        $this->assertGuest();
    }

    public function test_no_pierde_refresh_token_existente_si_google_no_lo_reenvia(): void
    {
        $usuario = User::factory()->create([
            'email' => 'coli@mallasarica.cl',
            'email_google' => 'coli@gmail.com',
            'google_refresh_token' => 'refresh-viejo',
        ]);
        $this->mockCuentaGoogle('coli@gmail.com', refreshToken: null);

        $this->getPublico('/auth/google/callback');

        $this->assertSame('refresh-viejo', $usuario->refresh()->google_refresh_token);
    }
}
