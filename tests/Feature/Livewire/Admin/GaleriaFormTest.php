<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Galeria\GaleriaForm;
use App\Models\GaleriaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class GaleriaFormTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actuarComoAdmin();
        Storage::fake('public');
    }

    public function test_alta_con_foto_nueva_la_guarda_en_disco(): void
    {
        Livewire::test(GaleriaForm::class)
            ->set('titulo', 'Ventana con malla')
            ->set('foto', UploadedFile::fake()->image('foto.jpg'))
            ->call('guardar')
            ->assertHasNoErrors();

        $item = GaleriaItem::first();
        $this->assertSame('Ventana con malla', $item->titulo);
        Storage::disk('public')->assertExists($item->foto_path);
    }

    public function test_editar_sin_reemplazar_foto_conserva_el_path_original(): void
    {
        $path = UploadedFile::fake()->image('original.jpg')->store('galeria', 'public');
        $item = GaleriaItem::create(['foto_path' => $path, 'titulo' => 'Original', 'orden' => 1, 'publicado' => true]);

        Livewire::test(GaleriaForm::class, ['editandoId' => $item->id])
            ->set('titulo', 'Título actualizado')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame($path, $item->fresh()->foto_path);
        $this->assertSame('Título actualizado', $item->fresh()->titulo);
    }

    public function test_rechaza_archivo_que_no_es_imagen(): void
    {
        Livewire::test(GaleriaForm::class)
            ->set('titulo', 'Ventana')
            ->set('foto', UploadedFile::fake()->create('documento.pdf', 100))
            ->call('guardar')
            ->assertHasErrors('foto');

        $this->assertDatabaseCount('galeria_items', 0);
    }
}
