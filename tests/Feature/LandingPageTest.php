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
}
