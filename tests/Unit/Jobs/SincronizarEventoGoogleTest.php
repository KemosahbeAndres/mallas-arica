<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SincronizarEventoGoogle;
use App\Models\Configuracion;
use App\Models\Evento;
use App\Models\User;
use App\Services\GoogleOAuthConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre la lógica de asignación/limpieza del mapa `google_event_ids` que no
 * depende de llamar a la API real de Google — usuarios sin Calendar
 * conectado (tieneGoogleCalendarConectado() === false) se saltan sin error,
 * que es el camino que se puede probar sin mockear el SDK de Google. El
 * intercambio real con la API (createEvent/updateEvent/deleteEvent) vive en
 * GoogleCalendarService, sin cobertura directa por lo mismo que
 * GoogleLoginController no mockea el SDK completo, solo Socialite.
 */
class SincronizarEventoGoogleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Configuracion::guardar('google_oauth.client_id', 'test-client-id');
        Configuracion::guardar('google_oauth.client_secret', 'test-client-secret');
    }

    public function test_sin_credenciales_configuradas_no_hace_nada(): void
    {
        Configuracion::guardar('google_oauth.client_id', null);
        Configuracion::guardar('google_oauth.client_secret', null);

        $evento = Evento::create(['titulo' => 'Test', 'tipo' => 'terreno', 'inicio' => now()]);
        $evento->usuarios()->attach(User::factory()->create());

        (new SincronizarEventoGoogle($evento))->handle(app(GoogleOAuthConfig::class));

        $this->assertNull($evento->refresh()->google_event_ids);
    }

    public function test_usuario_sin_calendar_conectado_no_genera_espejo(): void
    {
        $usuario = User::factory()->create(); // sin google_refresh_token
        $evento = Evento::create(['titulo' => 'Test', 'tipo' => 'terreno', 'inicio' => now()]);
        $evento->usuarios()->attach($usuario);

        (new SincronizarEventoGoogle($evento))->handle(app(GoogleOAuthConfig::class));

        $this->assertSame([], $evento->refresh()->google_event_ids ?? []);
    }

    public function test_evento_cancelado_sin_espejos_previos_no_falla(): void
    {
        $usuario = User::factory()->create();
        $evento = Evento::create([
            'titulo' => 'Test', 'tipo' => 'terreno', 'inicio' => now(), 'estado' => 'cancelado',
        ]);
        $evento->usuarios()->attach($usuario);

        (new SincronizarEventoGoogle($evento))->handle(app(GoogleOAuthConfig::class));

        $this->assertNull($evento->refresh()->google_event_ids);
    }

    public function test_usuario_quitado_sin_espejo_previo_no_falla(): void
    {
        $c1 = User::factory()->create();
        $c2 = User::factory()->create();
        $evento = Evento::create(['titulo' => 'Test', 'tipo' => 'terreno', 'inicio' => now()]);
        $evento->usuarios()->attach($c1);

        (new SincronizarEventoGoogle($evento))->handle(app(GoogleOAuthConfig::class));

        $evento->usuarios()->sync([$c2->id]);
        (new SincronizarEventoGoogle($evento->refresh()))->handle(app(GoogleOAuthConfig::class));

        $this->assertSame([], $evento->refresh()->google_event_ids ?? []);
    }
}
