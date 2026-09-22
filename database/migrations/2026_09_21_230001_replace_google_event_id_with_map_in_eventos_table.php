<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `google_event_id` (unique, un solo valor) se declaró en el Sprint 10
     * sin uso todavía, pensando en un solo calendario. Con la sincronización
     * real (cada usuario asignado tiene su propia copia en su propio Google
     * Calendar) hace falta un ID por usuario — `google_event_ids` guarda un
     * mapa {user_id: google_event_id}. Nunca se pobló en producción (columna
     * sin uso), así que no hay dato que migrar.
     */
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropUnique(['google_event_id']);
            $table->dropColumn('google_event_id');
        });

        Schema::table('eventos', function (Blueprint $table) {
            $table->json('google_event_ids')->nullable()->after('notas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropColumn('google_event_ids');
        });

        Schema::table('eventos', function (Blueprint $table) {
            $table->string('google_event_id')->nullable()->unique()->after('notas');
        });
    }
};
