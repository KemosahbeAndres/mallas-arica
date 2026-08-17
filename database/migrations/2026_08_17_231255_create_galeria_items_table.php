<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('galeria_items', function (Blueprint $table) {
            $table->id();
            $table->string('foto_path');
            $table->string('titulo');
            $table->foreignId('tipo_espacio_id')->nullable()->constrained('tipos_espacio')->nullOnDelete();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('publicado')->default(true);
            $table->timestamps();

            $table->index(['publicado', 'orden']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('galeria_items');
    }
};
