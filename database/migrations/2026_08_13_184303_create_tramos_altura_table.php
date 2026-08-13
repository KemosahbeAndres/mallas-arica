<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tramos_altura', function (Blueprint $table) {
            $table->id();
            $table->string('etiqueta');
            $table->decimal('altura_min', 4, 2);
            $table->decimal('altura_max', 4, 2)->nullable();
            $table->boolean('requiere_visita')->default(false);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tramos_altura');
    }
};
