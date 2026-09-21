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

    public function test_usuarios_es_una_seccion_real_para_el_super_admin(): void
    {
        $this->actuarComoAdmin();

        $this->getAdmin('/usuarios')
            ->assertOk()
            ->assertSee('Nuevo usuario');
    }

    public function test_usuarios_devuelve_403_para_colaborador(): void
    {
        $this->actuarComoUsuario('colaborador');

        $this->getAdmin('/usuarios')->assertForbidden();
    }

    public function test_ajustes_es_accesible_para_cualquier_rol_autenticado(): void
    {
        $this->actuarComoUsuario('supervisor');

        $this->getAdmin('/ajustes')
            ->assertOk()
            ->assertSee('Perfil');
    }

    public function test_ruta_perfil_redirige_a_ajustes(): void
    {
        $this->actuarComoUsuario('supervisor');

        $this->getAdmin('/perfil')->assertRedirect(route('admin.ajustes'));
    }

    public function test_colaborador_no_ve_links_de_cotizar_clientes_sitio_web_ni_usuarios(): void
    {
        $this->actuarComoUsuario('colaborador');

        $this->getAdmin('/resumen')
            ->assertOk()
            ->assertDontSee('Cotizar')
            ->assertDontSee('Sitio web')
            ->assertDontSee('Usuarios');
    }

    public function test_colaborador_ve_link_de_agenda_apuntando_a_mi_agenda(): void
    {
        $this->actuarComoUsuario('colaborador');

        $this->getAdmin('/resumen')->assertSee(route('admin.agenda.mia'), false);
    }

    public function test_supervisor_no_ve_link_de_sitio_web(): void
    {
        $this->actuarComoUsuario('supervisor');

        $this->getAdmin('/resumen')->assertDontSee('Sitio web');
    }

    public function test_supervisor_ve_cotizar_y_agenda(): void
    {
        $this->actuarComoUsuario('supervisor');

        $this->getAdmin('/resumen')
            ->assertSee('Cotizar')
            ->assertSee(route('admin.agenda'), false);
    }

    public function test_cualquier_rol_ve_ajustes_en_el_dropdown_de_usuario(): void
    {
        $this->actuarComoUsuario('colaborador');

        $this->getAdmin('/resumen')->assertSee('Ajustes');
    }

    public function test_solo_super_admin_ve_la_pestana_google_sso(): void
    {
        $this->actuarComoAdmin();
        $this->getAdmin('/ajustes')->assertSee('Google SSO');

        $this->actuarComoUsuario('administrador');
        $this->getAdmin('/ajustes')->assertDontSee('Google SSO');
    }

    public function test_administrador_no_puede_forzar_el_tab_de_google_sso(): void
    {
        $this->actuarComoUsuario('administrador');

        $this->getAdmin('/ajustes?tab=google')
            ->assertOk()
            ->assertDontSee('Redirect URI a autorizar');
    }
}
