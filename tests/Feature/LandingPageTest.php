<?php

namespace Tests\Feature;

use Database\Seeders\TipoEspacioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_page_loads_successfully(): void
    {
        $this->seed(TipoEspacioSeeder::class);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Mallas Arica');
        $response->assertSee('Ventanas');
    }

    public function test_la_landing_incluye_schema_local_business_y_faq(): void
    {
        $this->seed(TipoEspacioSeeder::class);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('"@type":"LocalBusiness"', false);
        $response->assertSee('"@type":"FAQPage"', false);
    }

    public function test_el_sitemap_responde_como_xml_e_incluye_la_home(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee(url('/'), false);
    }

    public function test_robots_txt_referencia_el_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertStatus(200);
        $response->assertSee('Sitemap: '.url('/sitemap.xml'));
    }

    public function test_rutas_antiguas_de_wix_redirigen_301(): void
    {
        $this->get('/page4')->assertRedirect('/#nosotros')->assertStatus(301);
        $this->get('/servicios')->assertRedirect('/#servicios')->assertStatus(301);
        $this->get('/faq')->assertRedirect('/#faq')->assertStatus(301);
    }
}
