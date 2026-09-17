<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Media\MediaLibrary;
use App\Models\MediaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class MediaLibraryTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actuarComoAdmin();
        Storage::fake('public');
    }

    public function test_sube_una_imagen_nueva_a_la_libreria(): void
    {
        Livewire::test(MediaLibrary::class)
            ->set('titulo', 'Ventana con malla')
            ->set('foto', UploadedFile::fake()->image('foto.jpg'))
            ->call('subir')
            ->assertHasNoErrors();

        $item = MediaItem::first();
        $this->assertSame('Ventana con malla', $item->titulo);
        $this->assertNull($item->media_album_id);
        Storage::disk('public')->assertExists($item->archivo_path);
    }

    public function test_rechaza_archivo_que_no_es_imagen(): void
    {
        Livewire::test(MediaLibrary::class)
            ->set('foto', UploadedFile::fake()->create('documento.pdf', 100))
            ->call('subir')
            ->assertHasErrors('foto');

        $this->assertDatabaseCount('media_items', 0);
    }

    public function test_eliminar_borra_archivo_y_registro(): void
    {
        $path = UploadedFile::fake()->image('foto.jpg')->store('media', 'public');
        $item = MediaItem::create(['archivo_path' => $path, 'titulo' => 'Foto', 'orden' => 1]);

        Livewire::test(MediaLibrary::class)->call('eliminar', $item->id);

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('media_items', ['id' => $item->id]);
    }
}
