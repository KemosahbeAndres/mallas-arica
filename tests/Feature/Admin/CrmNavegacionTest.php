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

        $this->get('/admin')->assertRedirect('/admin/resumen');
    }

    public function test_resumen_ya_es_una_seccion_real(): void
    {
        $this->actuarComoAdmin();

        $this->get('/admin/resumen')
            ->assertOk()
            ->assertSee('Ticket promedio')
            ->assertDontSee('🚧');
    }

    public function test_clientes_ya_es_una_seccion_real(): void
    {
        $this->actuarComoAdmin();

        $this->get('/admin/clientes')
            ->assertOk()
            ->assertSee('Nuevo cliente')
            ->assertDontSee('próximamente');
    }

    public function test_agenda_ya_es_una_seccion_real(): void
    {
        $this->actuarComoAdmin();

        $this->get('/admin/agenda')
            ->assertOk()
            ->assertSee('Por agendar')
            ->assertDontSee('🚧');
    }

    public function test_cotizaciones_ya_es_una_seccion_real(): void
    {
        $this->actuarComoAdmin();

        $this->get('/admin/cotizaciones')
            ->assertOk()
            ->assertSee('Nueva cotización')
            ->assertDontSee('🚧');
    }

    public function test_invitado_es_redirigido_al_login_desde_una_seccion_del_crm(): void
    {
        $this->get('/admin/clientes')->assertRedirect('/admin/login');
    }
}
