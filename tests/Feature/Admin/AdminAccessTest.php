<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_es_redirigido_al_login_desde_cotizaciones(): void
    {
        $this->getAdmin('/admin/cotizaciones')->assertRedirect(route('admin.login'));
    }

    public function test_invitado_es_redirigido_al_login_desde_clientes(): void
    {
        $this->getAdmin('/admin/clientes')->assertRedirect(route('admin.login'));
    }

    public function test_invitado_es_redirigido_al_login_desde_galeria(): void
    {
        $this->getAdmin('/admin/galeria')->assertRedirect(route('admin.login'));
    }
}
