<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_direcciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('direccion');            // texto libre, ej. "Los Aromos 221, Arica"
            $table->string('etiqueta')->nullable(); // opcional, ej. "Casa", "Depto playa"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_direcciones');
    }
};
