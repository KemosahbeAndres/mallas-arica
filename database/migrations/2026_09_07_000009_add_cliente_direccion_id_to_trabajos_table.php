<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 13 — la OT se agrupa por dirección en la ficha de Cliente (mockup
 * pág. 5). Se llena desde `cotizaciones.cliente_direccion_id` al crear la OT.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trabajos', function (Blueprint $table) {
            $table->foreignId('cliente_direccion_id')
                ->nullable()
                ->after('cliente_id')
                ->constrained('cliente_direcciones')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trabajos', function (Blueprint $table) {
            $table->dropForeign(['cliente_direccion_id']);
            $table->dropColumn('cliente_direccion_id');
        });
    }
};
