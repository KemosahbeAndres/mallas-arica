<?php

namespace Tests\Feature\Livewire;

use App\Livewire\SolicitudContacto;
use App\Models\Cotizacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class SolicitudContactoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El throttle de SolicitudContacto vive en el RateLimiter, no en la BD:
        // RefreshDatabase no lo resetea entre tests, así que hay que limpiarlo a mano.
        RateLimiter::clear('solicitud-contacto:127.0.0.1');
    }

    public function test_la_solicitud_se_guarda_como_lead_sin_calculo_de_precio(): void
    {
        Livewire::test(SolicitudContacto::class)
            ->set('nombre', 'Juan Pérez')
            ->set('telefono', '+56912345678')
            ->set('direccion', 'Condominio Las Torres, depto 302')
            ->set('email', 'juan@correo.cl')
            ->call('enviar')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('cotizaciones', 1);

        $cotizacion = Cotizacion::first();

        $this->assertSame('Juan Pérez', $cotizacion->nombre);
        $this->assertSame('web', $cotizacion->canal);
        $this->assertSame('borrador', $cotizacion->estado);
        $this->assertTrue($cotizacion->requiere_visita);
        $this->assertSame(0, $cotizacion->total_min);
        $this->assertSame(0, $cotizacion->total_max);
        $this->assertCount(0, $cotizacion->items);
    }

    public function test_el_numero_generado_es_el_correlativo_del_id(): void
    {
        Livewire::test(SolicitudContacto::class)
            ->set('nombre', 'Juan Pérez')
            ->set('telefono', '+56912345678')
            ->set('direccion', 'Av. Siempre Viva 123')
            ->call('enviar')
            ->assertSet('numeroGenerado', Cotizacion::first()->numero);
    }

    public function test_email_es_opcional(): void
    {
        Livewire::test(SolicitudContacto::class)
            ->set('nombre', 'Juan Pérez')
            ->set('telefono', '+56912345678')
            ->set('direccion', 'Av. Siempre Viva 123')
            ->call('enviar')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('cotizaciones', 1);
        $this->assertNull(Cotizacion::first()->email);
    }

    public function test_no_persiste_sin_nombre_telefono_o_direccion(): void
    {
        Livewire::test(SolicitudContacto::class)
            ->call('enviar')
            ->assertHasErrors(['nombre', 'telefono', 'direccion']);

        $this->assertDatabaseCount('cotizaciones', 0);
    }

    public function test_email_invalido_no_persiste(): void
    {
        Livewire::test(SolicitudContacto::class)
            ->set('nombre', 'Juan Pérez')
            ->set('telefono', '+56912345678')
            ->set('direccion', 'Av. Siempre Viva 123')
            ->set('email', 'no-es-un-correo')
            ->call('enviar')
            ->assertHasErrors(['email']);

        $this->assertDatabaseCount('cotizaciones', 0);
    }

    public function test_honeypot_relleno_ignora_el_envio_en_silencio(): void
    {
        Livewire::test(SolicitudContacto::class)
            ->set('nombre', 'Bot')
            ->set('telefono', '000000')
            ->set('direccion', 'Nowhere')
            ->set('sitioWeb', 'https://spam.example')
            ->call('enviar')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('cotizaciones', 0);
    }

    public function test_throttle_bloquea_tras_exceder_el_maximo_de_intentos(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Livewire::test(SolicitudContacto::class)
                ->set('nombre', 'Juan Pérez')
                ->set('telefono', '+56912345678')
                ->set('direccion', 'Av. Siempre Viva 123')
                ->call('enviar');
        }

        Livewire::test(SolicitudContacto::class)
            ->set('nombre', 'Juan Pérez')
            ->set('telefono', '+56912345678')
            ->set('direccion', 'Av. Siempre Viva 123')
            ->call('enviar')
            ->assertHasErrors(['throttle']);

        $this->assertDatabaseCount('cotizaciones', 5);
    }
}
