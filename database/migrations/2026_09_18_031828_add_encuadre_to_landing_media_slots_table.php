<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Encuadre (object-fit) y posición (object-position, 0-100%) con los que
     * se muestra la imagen/álbum del slot en la landing. Vive en el slot, no
     * en el MediaItem: la misma imagen puede usarse en distintas secciones
     * con distinto recorte. posicion_x/y son porcentajes enteros (50 = centro).
     */
    public function up(): void
    {
        Schema::table('landing_media_slots', function (Blueprint $table) {
            $table->string('encuadre', 10)->default('cover')->after('media_album_id');
            $table->unsignedTinyInteger('posicion_x')->default(50)->after('encuadre');
            $table->unsignedTinyInteger('posicion_y')->default(50)->after('posicion_x');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landing_media_slots', function (Blueprint $table) {
            $table->dropColumn(['encuadre', 'posicion_x', 'posicion_y']);
        });
    }
};
