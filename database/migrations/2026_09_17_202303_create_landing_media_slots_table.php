<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Una fila por sección de la landing con imagen editable (hero, nosotros,
     * galería pública). `slug` identifica la sección; solo una de las dos FK
     * (media_item_id / media_album_id) se usa según el tipo de sección —
     * secciones de imagen única usan media_item_id, la galería usa
     * media_album_id. Filas sembradas por el seeder, nunca creadas desde el
     * panel (no hay alta/baja de secciones, solo asignación).
     */
    public function up(): void
    {
        Schema::create('landing_media_slots', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('media_item_id')->nullable()->constrained('media_items')->nullOnDelete();
            $table->foreignId('media_album_id')->nullable()->constrained('media_albums')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_media_slots');
    }
};
