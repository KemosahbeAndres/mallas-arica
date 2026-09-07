<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Galeria\GaleriaIndex;
use App\Models\GaleriaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class GaleriaIndexTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actuarComoAdmin();
        Storage::fake('public');
    }

    public function test_toggle_publicado(): void
    {
        $item = GaleriaItem::create([
            'foto_path' => UploadedFile::fake()->image('foto.jpg')->store('galeria', 'public'),
            'titulo' => 'Ventana',
            'orden' => 1,
            'publicado' => false,
        ]);

        Livewire::test(GaleriaIndex::class)->call('togglePublicado', $item->id);

        $this->assertTrue($item->fresh()->publicado);
    }

    public function test_mover_arriba_intercambia_orden(): void
    {
        $primero = GaleriaItem::create(['foto_path' => 'a.jpg', 'titulo' => 'A', 'orden' => 1, 'publicado' => true]);
        $segundo = GaleriaItem::create(['foto_path' => 'b.jpg', 'titulo' => 'B', 'orden' => 2, 'publicado' => true]);

        Livewire::test(GaleriaIndex::class)->call('moverArriba', $segundo->id);

        $this->assertSame(2, $primero->fresh()->orden);
        $this->assertSame(1, $segundo->fresh()->orden);
    }

    public function test_eliminar_borra_archivo_y_registro(): void
    {
        $path = UploadedFile::fake()->image('foto.jpg')->store('galeria', 'public');
        $item = GaleriaItem::create(['foto_path' => $path, 'titulo' => 'Ventana', 'orden' => 1, 'publicado' => true]);

        Livewire::test(GaleriaIndex::class)->call('eliminar', $item->id);

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('galeria_items', ['id' => $item->id]);
    }
}
