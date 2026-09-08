<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_es_redirigido_al_login_desde_cotizaciones(): void
    {
        $this->get('/admin/cotizaciones')->assertRedirect('/admin/login');
    }

    public function test_invitado_es_redirigido_al_login_desde_clientes(): void
    {
        $this->get('/admin/clientes')->assertRedirect('/admin/login');
    }

    public function test_invitado_es_redirigido_al_login_desde_galeria(): void
    {
        $this->get('/admin/galeria')->assertRedirect('/admin/login');
    }
}
