<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\SitioWeb\ContenidoForm;
use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class ContenidoFormTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComoAdmin();

        $this->campo = SiteContent::create([
            'key' => 'hero.titulo_1',
            'value' => 'Original',
            'grupo' => 'hero',
            'label' => 'Título',
            'tipo' => 'text',
            'orden' => 0,
        ]);
    }

    private SiteContent $campo;

    public function test_guardar_persiste_el_nuevo_valor(): void
    {
        Livewire::test(ContenidoForm::class)
            ->assertSet("valores.{$this->campo->id}", 'Original')
            ->set("valores.{$this->campo->id}", 'Editado desde el panel')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame('Editado desde el panel', $this->campo->fresh()->value);
    }

    public function test_valor_vacio_se_guarda_como_null(): void
    {
        Livewire::test(ContenidoForm::class)
            ->set("valores.{$this->campo->id}", '   ')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertNull($this->campo->fresh()->value);
    }

    public function test_rechaza_valores_demasiado_largos(): void
    {
        Livewire::test(ContenidoForm::class)
            ->set("valores.{$this->campo->id}", str_repeat('x', 2001))
            ->call('guardar')
            ->assertHasErrors("valores.{$this->campo->id}");

        $this->assertSame('Original', $this->campo->fresh()->value);
    }

    public function test_invitado_no_accede_al_panel_de_sitio_web(): void
    {
        Auth::logout();

        $this->get('/admin/sitio-web')->assertRedirect('/admin/login');
    }
}
