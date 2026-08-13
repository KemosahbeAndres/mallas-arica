<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_espacio_id')->constrained('tipos_espacio')->cascadeOnDelete();
            $table->foreignId('tramo_altura_id')->constrained('tramos_altura')->cascadeOnDelete();
            $table->unsignedInteger('precio_ml_min');
            $table->unsignedInteger('precio_ml_max');
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->timestamps();

            $table->unique(
                ['tipo_espacio_id', 'tramo_altura_id', 'vigente_desde'],
                'tarifas_espacio_tramo_vigencia_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifas');
    }
};
