<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migra los `galeria_items` existentes (si hay) al nuevo modelo de
     * librería multimedia: crea el álbum "Galería pública", copia cada fila
     * a `media_items` conservando foto/título/orden, y crea los 3 slots de
     * secciones de landing (hero, nosotros sin asignar; galería apuntando al
     * álbum recién creado). `galeria_items` se conserva sin tocar — el drop
     * de esa tabla queda para un sprint posterior una vez confirmado en prod.
     */
    public function up(): void
    {
        $albumId = DB::table('media_albums')->insertGetId([
            'nombre' => 'Galería pública',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $items = DB::table('galeria_items')->orderBy('orden')->get();

        foreach ($items as $item) {
            DB::table('media_items')->insert([
                'archivo_path' => $item->foto_path,
                'titulo' => $item->titulo,
                'media_album_id' => $albumId,
                'orden' => $item->orden,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $ahora = now();

        DB::table('landing_media_slots')->insert([
            ['slug' => 'hero', 'media_item_id' => null, 'media_album_id' => null, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['slug' => 'nosotros', 'media_item_id' => null, 'media_album_id' => null, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['slug' => 'galeria-publica', 'media_item_id' => null, 'media_album_id' => $albumId, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('landing_media_slots')->whereIn('slug', ['hero', 'nosotros', 'galeria-publica'])->delete();
        DB::table('media_items')->whereNotNull('media_album_id')->delete();
        DB::table('media_albums')->where('nombre', 'Galería pública')->delete();
    }
};
