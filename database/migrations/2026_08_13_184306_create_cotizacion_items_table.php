<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->foreignId('tipo_espacio_id')->constrained('tipos_espacio');
            $table->foreignId('tipo_malla_id')->nullable()->constrained('tipos_malla');
            $table->foreignId('tramo_altura_id')->nullable()->constrained('tramos_altura');
            $table->decimal('metros_lineales', 6, 2);
            $table->unsignedInteger('precio_ml_min_snapshot')->nullable();
            $table->unsignedInteger('precio_ml_max_snapshot')->nullable();
            $table->decimal('multiplicador_snapshot', 4, 2)->nullable();
            $table->unsignedInteger('subtotal_min')->default(0);
            $table->unsignedInteger('subtotal_max')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_items');
    }
};
