<?php

namespace Tests\Unit;

use App\Models\Faq;
use App\Models\SiteContent;
use App\Services\SiteContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteContentServiceTest extends TestCase
{
    use RefreshDatabase;

    private SiteContentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SiteContentService::class);
    }

    public function test_get_devuelve_el_valor_guardado(): void
    {
        SiteContent::create([
            'key' => 'hero.titulo_1',
            'value' => 'Hola Arica',
            'grupo' => 'hero',
            'label' => 'Título',
            'tipo' => 'text',
        ]);

        $this->assertSame('Hola Arica', $this->service->get('hero.titulo_1'));
    }

    public function test_get_cae_al_default_si_la_key_no_existe_o_esta_vacia(): void
    {
        SiteContent::create([
            'key' => 'hero.badge',
            'value' => '',
            'grupo' => 'hero',
            'label' => 'Badge',
            'tipo' => 'text',
        ]);

        $this->assertSame('def', $this->service->get('hero.inexistente', 'def'));
        $this->assertSame('def', $this->service->get('hero.badge', 'def'));
    }

    public function test_guardar_un_contenido_invalida_el_cache(): void
    {
        $fila = SiteContent::create([
            'key' => 'nosotros.titulo',
            'value' => 'Antes',
            'grupo' => 'nosotros',
            'label' => 'Título',
            'tipo' => 'text',
        ]);

        $this->assertSame('Antes', $this->service->get('nosotros.titulo'));

        $fila->update(['value' => 'Después']);

        $this->assertSame('Después', $this->service->get('nosotros.titulo'));
    }

    public function test_faqs_solo_devuelve_publicadas_ordenadas_y_reacciona_a_cambios(): void
    {
        Faq::create(['pregunta' => 'B', 'respuesta' => 'rb', 'orden' => 2, 'publicada' => true]);
        Faq::create(['pregunta' => 'A', 'respuesta' => 'ra', 'orden' => 1, 'publicada' => true]);
        $oculta = Faq::create(['pregunta' => 'Oculta', 'respuesta' => 'ro', 'orden' => 3, 'publicada' => false]);

        $this->assertSame(['A', 'B'], $this->service->faqs()->pluck('pregunta')->all());

        $oculta->update(['publicada' => true]);

        $this->assertSame(['A', 'B', 'Oculta'], $this->service->faqs()->pluck('pregunta')->all());
    }
}
