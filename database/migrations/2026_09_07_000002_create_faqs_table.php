<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('pregunta');
            $table->text('respuesta');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('publicada')->default(true);
            $table->timestamps();

            $table->index(['publicada', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
