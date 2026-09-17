<?php

namespace Database\Seeders;

use App\Models\LandingMediaSlot;
use App\Models\MediaAlbum;
use App\Models\MediaItem;
use Illuminate\Database\Seeder;

/**
 * Librería multimedia inicial: siembra el álbum "Galería pública" con las
 * mismas 6 imágenes SVG placeholder de marca que antes vivían en
 * GaleriaItemSeeder (galeria_items, eliminado tras migrar a media_items), y
 * los 3 slots de secciones de landing (hero/nosotros sin asignar todavía,
 * galería pública apuntando al álbum recién creado). Idempotente.
 */
class MediaLibrarySeeder extends Seeder
{
    public function run(): void
    {
        $album = MediaAlbum::firstOrCreate(['nombre' => 'Galería pública']);

        $items = [
            ['slug' => 'ventana', 'titulo' => 'Malla en ventana de departamento'],
            ['slug' => 'balcon', 'titulo' => 'Cierre perimetral de balcón'],
            ['slug' => 'terraza', 'titulo' => 'Protección de terraza abierta'],
            ['slug' => 'escalera', 'titulo' => 'Barrera de seguridad en escalera'],
            ['slug' => 'mascotas', 'titulo' => 'Malla reforzada para mascotas'],
            ['slug' => 'piscina', 'titulo' => 'Cerco de protección para piscina'],
        ];

        foreach ($items as $index => $item) {
            MediaItem::updateOrCreate(
                ['archivo_path' => "galeria/{$item['slug']}-1.svg"],
                [
                    'titulo' => $item['titulo'],
                    'media_album_id' => $album->id,
                    'orden' => $index + 1,
                ],
            );
        }

        foreach (['hero', 'nosotros'] as $slug) {
            LandingMediaSlot::firstOrCreate(['slug' => $slug]);
        }

        LandingMediaSlot::updateOrCreate(
            ['slug' => 'galeria-publica'],
            ['media_album_id' => $album->id],
        );
    }
}
