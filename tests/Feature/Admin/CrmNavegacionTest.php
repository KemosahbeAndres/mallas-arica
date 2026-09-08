<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class CrmNavegacionTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    public function test_las_secciones_no_construidas_muestran_proximamente(): void
    {
        $this->actuarComoAdmin();

        foreach (['resumen', 'cotizaciones'] as $seccion) {
            $this->get("/admin/{$seccion}")
                ->assertOk()
                ->assertSee('próximamente');
        }
    }

    public function test_la_raiz_del_admin_redirige_a_sitio_web(): void
    {
        $this->actuarComoAdmin();

        $this->get('/admin')->assertRedirect('/admin/sitio-web');
    }

    public function test_clientes_ya_es_una_seccion_real(): void
    {
        $this->actuarComoAdmin();

        $this->get('/admin/clientes')
            ->assertOk()
            ->assertSee('Nuevo cliente')
            ->assertDontSee('próximamente');
    }

    public function test_calendario_ya_es_una_seccion_real(): void
    {
        $this->actuarComoAdmin();

        $this->get('/admin/calendario')
            ->assertOk()
            ->assertSee('Agenda semanal')
            ->assertDontSee('🚧');
    }

    public function test_invitado_es_redirigido_al_login_desde_una_seccion_del_crm(): void
    {
        $this->get('/admin/clientes')->assertRedirect('/admin/login');
    }
}
