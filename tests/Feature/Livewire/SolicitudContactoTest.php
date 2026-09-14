<?php

namespace Tests\Feature\Livewire;

use App\Jobs\NotificarNuevoCliente;
use App\Livewire\SolicitudContacto;
use App\Mail\NuevoClienteAdmin;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class SolicitudContactoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        // El throttle vive en el RateLimiter, no en la BD: RefreshDatabase no lo
        // resetea entre tests, así que hay que limpiarlo a mano.
        RateLimiter::clear('solicitud-contacto:127.0.0.1');
    }

    public function test_el_formulario_crea_un_cliente_con_su_direccion(): void
    {
        Livewire::test(SolicitudContacto::class)
            ->set('nombre', 'Juan Pérez')
            ->set('telefono', '+56912345678')
            ->set('direccion', 'Condominio Las Torres, depto 302')
            ->set('email', 'juan@correo.cl')
            ->call('enviar')
            ->assertHasNoErrors()
            ->assertSet('enviado', true);

        $this->assertDatabaseCount('clientes', 1);

        $cliente = Cliente::with('direcciones')->first();
        $this->assertSame('Juan Pérez', $cliente->nombre);
        $this->assertSame('juan@correo.cl', $cliente->email);
        $this->assertCount(1, $cliente->direcciones);
        $this->assertSame('Condominio Las Torres, depto 302', $cliente->direcciones->first()->direccion);
    }

    public function test_reenviar_con_el_mismo_telefono_no_duplica_el_cliente(): void
    {
        $enviar = fn () => Livewire::test(SolicitudContacto::class)
            ->set('nombre', 'Juan Pérez')
            ->set('telefono', '+56912345678')
            ->set('direccion', 'Av. Siempre Viva 123')
            ->call('enviar');

        $enviar();
        RateLimiter::clear('solicitud-contacto:127.0.0.1');
        $enviar();

        $this->assertDatabaseCount('clientes', 1);
        $this->assertCount(1, Cliente::first()->direcciones); // misma dirección, no se duplica
    }

    public function test_encola_el_aviso_al_dueno(): void
    {
        Bus::fake();

        Livewire::test(SolicitudContacto::class)
            ->set('nombre', 'Juan Pérez')
            ->set('telefono', '+56912345678')
            ->set('direccion', 'Av. Siempre Viva 123')
            ->call('enviar');

        Bus::assertDispatched(NotificarNuevoCliente::class);
    }

    public function test_el_job_envia_el_correo_y_marca_notificado_at(): void
    {
        $cliente = Cliente::create(['nombre' => 'Ana', 'telefono' => '+56900000000']);

        (new NotificarNuevoCliente($cliente))->handle();

        Mail::assertQueued(NuevoClienteAdmin::class);
        $this->assertNotNull($cliente->fresh()->notificado_at);
    }

    public function test_el_job_es_idempotente(): void
    {
        $cliente = Cliente::create(['nombre' => 'Ana', 'telefono' => '+56900000000']);
        $cliente->forceFill(['notificado_at' => now()])->save();

        (new NotificarNuevoCliente($cliente))->handle();

        Mail::assertNothingQueued();
    }

    public function test_email_es_opcional(): void
    {
        Livewire::test(SolicitudContacto::class)
            ->set('nombre', 'Juan Pérez')
            ->set('telefono', '+56912345678')
            ->set('direccion', 'Av. Siempre Viva 123')
            ->call('enviar')
            ->assertHasNoErrors();

        $this->assertNull(Cliente::first()->email);
    }

    public function test_no_persiste_sin_nombre_telefono_o_direccion(): void
    {
        Livewire::test(SolicitudContacto::class)
            ->call('enviar')
            ->assertHasErrors(['nombre', 'telefono', 'direccion']);

        $this->assertDatabaseCount('clientes', 0);
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

        $this->assertDatabaseCount('clientes', 0);
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

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_throttle_bloquea_tras_exceder_el_maximo_de_intentos(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Livewire::test(SolicitudContacto::class)
                ->set('nombre', 'Cliente '.$i)
                ->set('telefono', '+5691234567'.$i)
                ->set('direccion', 'Av. Siempre Viva 123')
                ->call('enviar');
        }

        Livewire::test(SolicitudContacto::class)
            ->set('nombre', 'Juan Pérez')
            ->set('telefono', '+56900000099')
            ->set('direccion', 'Av. Siempre Viva 123')
            ->call('enviar')
            ->assertHasErrors(['throttle']);

        $this->assertDatabaseCount('clientes', 5);
    }
}
