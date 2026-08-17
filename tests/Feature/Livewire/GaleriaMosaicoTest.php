<?php

namespace Tests\Feature\Livewire;

use App\Livewire\GaleriaMosaico;
use App\Models\GaleriaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GaleriaMosaicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_solo_items_publicados_ordenados(): void
    {
        GaleriaItem::create([
            'foto_path' => 'galeria/segunda.svg',
            'titulo' => 'Segunda foto',
            'orden' => 2,
            'publicado' => true,
        ]);

        GaleriaItem::create([
            'foto_path' => 'galeria/oculta.svg',
            'titulo' => 'Foto oculta',
            'orden' => 1,
            'publicado' => false,
        ]);

        GaleriaItem::create([
            'foto_path' => 'galeria/primera.svg',
            'titulo' => 'Primera foto',
            'orden' => 1,
            'publicado' => true,
        ]);

        Livewire::test(GaleriaMosaico::class)
            ->assertSee('Primera foto')
            ->assertSee('Segunda foto')
            ->assertDontSee('Foto oculta');
    }

    public function test_muestra_mensaje_cuando_no_hay_items_publicados(): void
    {
        Livewire::test(GaleriaMosaico::class)
            ->assertSee('Muy pronto vamos a publicar fotos');
    }
}
