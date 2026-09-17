<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class CrmNavegacionTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    public function test_la_raiz_del_admin_redirige_a_resumen(): void
    {
        $this->actuarComoAdmin();

        $this->getAdmin('/')->assertRedirect(route('admin.resumen'));
    }

    public function test_resumen_ya_es_una_seccion_real(): void
    {
        $this->actuarComoAdmin();

        $this->getAdmin('/resumen')
            ->assertOk()
            ->assertSee('Ticket promedio')
            ->assertDontSee('🚧');
    }

    public function test_clientes_ya_es_una_seccion_real(): void
    {
        $this->actuarComoAdmin();

        $this->getAdmin('/clientes')
            ->assertOk()
            ->assertSee('Nuevo cliente')
            ->assertDontSee('próximamente');
    }

    public function test_agenda_ya_es_una_seccion_real(): void
    {
        $this->actuarComoAdmin();

        $this->getAdmin('/agenda')
            ->assertOk()
            ->assertSee('Por agendar')
            ->assertDontSee('🚧');
    }

    public function test_cotizaciones_ya_es_una_seccion_real(): void
    {
        $this->actuarComoAdmin();

        $this->getAdmin('/cotizaciones')
            ->assertOk()
            ->assertSee('Nueva cotización')
            ->assertDontSee('🚧');
    }

    public function test_invitado_es_redirigido_al_login_desde_una_seccion_del_crm(): void
    {
        $this->getAdmin('/clientes')->assertRedirect(route('admin.login'));
    }
}
