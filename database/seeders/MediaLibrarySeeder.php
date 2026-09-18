<?php

namespace Database\Seeders;

use App\Models\LandingMediaSlot;
use App\Models\MediaAlbum;
use Illuminate\Database\Seeder;

/**
 * Librería multimedia inicial: crea el álbum "Galería pública" (vacío — sin
 * fotos reales todavía) y los 3 slots de secciones de landing (hero/nosotros
 * sin asignar, galería pública ya apuntando al álbum para que el dueño solo
 * tenga que subir fotos y agregarlas ahí). No siembra ningún MediaItem: un
 * placeholder de arte de marca no es una foto de trabajo real, y sembrarlo
 * como si fuera una imagen de la librería hacía que apareciera borrable
 * desde el panel como si el dueño ya hubiera cargado contenido. El estado
 * "sin imágenes" se resuelve visualmente en el Blade (placeholder), no con
 * datos falsos en BD. Idempotente.
 */
class MediaLibrarySeeder extends Seeder
{
    public function run(): void
    {
        $album = MediaAlbum::firstOrCreate(['nombre' => 'Galería pública']);

        foreach (['hero', 'nosotros'] as $slug) {
            LandingMediaSlot::firstOrCreate(['slug' => $slug]);
        }

        LandingMediaSlot::updateOrCreate(
            ['slug' => 'galeria-publica'],
            ['media_album_id' => $album->id],
        );
    }
}
