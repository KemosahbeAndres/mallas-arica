<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_malla', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('nombre');
            $table->decimal('grosor_mm', 4, 2);
            $table->decimal('rombo_cm', 4, 2);
            $table->decimal('multiplicador', 4, 2)->default(1.00);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_malla');
    }
};
