<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OT (Orden de Trabajo) — CLAUDE.md §11 ter. Salida a terreno con un objetivo.
 * Se crea cuando una cotización pasa a `aceptada`. Doble relación cliente_id +
 * cotizacion_id. Apunta a su evento de agenda vía evento_id (no al revés).
 *
 * Esta es la versión mínima del Sprint 12: sin medidas_finales / firma / fotos
 * / consumos — eso es Etapa 2 (app móvil de instaladores).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trabajos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('cotizacion_id')->nullable()->constrained('cotizaciones')->nullOnDelete();
            $table->foreignId('evento_id')->nullable()->constrained('eventos')->nullOnDelete();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('estado')->default('pendiente'); // pendiente | en_curso | ejecutada | cancelada
            $table->unsignedSmallInteger('meses_mantencion')->default(12);
            $table->timestamp('finalizado_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['estado', 'finalizado_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trabajos');
    }
};
