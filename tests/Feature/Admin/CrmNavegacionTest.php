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

        foreach (['resumen', 'cotizaciones', 'clientes', 'calendario'] as $seccion) {
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

    public function test_invitado_es_redirigido_al_login_desde_una_seccion_del_crm(): void
    {
        $this->get('/admin/clientes')->assertRedirect('/admin/login');
    }
}
