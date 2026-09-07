<?php

namespace Tests\Feature\Notificaciones;

use App\Jobs\EnviarNotificacionesCotizacion;
use App\Livewire\SolicitudContacto;
use App\Mail\CopiaCotizacionCliente;
use App\Mail\NuevaCotizacionAdmin;
use App\Models\Cotizacion;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class CotizacionNotificacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('solicitud-contacto:127.0.0.1');
    }

    public function test_se_encola_el_job_al_persistir_la_cotizacion(): void
    {
        Bus::fake();

        Livewire::test(SolicitudContacto::class)
            ->set('nombre', 'Juan Pérez')
            ->set('telefono', '+56912345678')
            ->set('direccion', 'Av. Siempre Viva 123')
            ->call('enviar')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('cotizaciones', 1);

        Bus::assertDispatched(EnviarNotificacionesCotizacion::class);
    }

    public function test_la_cotizacion_persiste_aunque_falle_el_encolado(): void
    {
        $dispatcher = \Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->andThrow(new \RuntimeException('Redis caído'));
        $dispatcher->shouldReceive('dispatchAfterResponse')->andThrow(new \RuntimeException('Redis caído'));
        App::instance(Dispatcher::class, $dispatcher);

        Livewire::test(SolicitudContacto::class)
            ->set('nombre', 'Juan Pérez')
            ->set('telefono', '+56912345678')
            ->set('direccion', 'Av. Siempre Viva 123')
            ->call('enviar');

        $this->assertDatabaseCount('cotizaciones', 1);
    }

    public function test_el_aviso_al_dueno_lleva_reply_to_del_cliente(): void
    {
        Mail::fake();

        $cotizacion = Cotizacion::create([
            'nombre' => 'Juan Pérez',
            'telefono' => '+56912345678',
            'email' => 'cliente@correo.cl',
            'canal' => 'web',
            'estado' => 'borrador',
            'requiere_visita' => true,
        ]);

        (new EnviarNotificacionesCotizacion($cotizacion))->handle();

        Mail::assertQueued(NuevaCotizacionAdmin::class, fn ($mail) => $mail->hasReplyTo('cliente@correo.cl'));
    }

    public function test_no_se_envia_copia_si_el_cliente_no_dejo_email(): void
    {
        Mail::fake();

        $cotizacion = Cotizacion::create([
            'nombre' => 'Juan Pérez',
            'telefono' => '+56912345678',
            'canal' => 'web',
            'estado' => 'borrador',
            'requiere_visita' => true,
        ]);

        (new EnviarNotificacionesCotizacion($cotizacion))->handle();

        Mail::assertQueued(NuevaCotizacionAdmin::class);
        Mail::assertNotQueued(CopiaCotizacionCliente::class);
    }

    public function test_el_job_es_idempotente(): void
    {
        Mail::fake();

        $cotizacion = Cotizacion::create([
            'nombre' => 'Juan Pérez',
            'telefono' => '+56912345678',
            'canal' => 'web',
            'estado' => 'borrador',
            'requiere_visita' => true,
            'notificado_at' => now(),
        ]);

        (new EnviarNotificacionesCotizacion($cotizacion))->handle();

        Mail::assertNothingSent();
    }

    public function test_el_job_marca_notificado_at(): void
    {
        Mail::fake();

        $cotizacion = Cotizacion::create([
            'nombre' => 'Juan Pérez',
            'telefono' => '+56912345678',
            'canal' => 'web',
            'estado' => 'borrador',
            'requiere_visita' => true,
        ]);

        (new EnviarNotificacionesCotizacion($cotizacion))->handle();

        $this->assertNotNull($cotizacion->fresh()->notificado_at);
    }

    public function test_no_adjunta_pdf_si_la_cotizacion_no_tiene_items(): void
    {
        Mail::fake();

        $cotizacion = Cotizacion::create([
            'nombre' => 'Juan Pérez',
            'telefono' => '+56912345678',
            'email' => 'cliente@correo.cl',
            'canal' => 'web',
            'estado' => 'borrador',
            'requiere_visita' => true,
        ]);

        (new EnviarNotificacionesCotizacion($cotizacion))->handle();

        Mail::assertQueued(CopiaCotizacionCliente::class, function (CopiaCotizacionCliente $mail) {
            return $mail->attachments() === [];
        });
    }
}
