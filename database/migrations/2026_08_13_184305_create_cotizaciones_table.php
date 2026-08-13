<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->string('telefono');
            $table->string('email')->nullable();
            $table->string('comuna')->nullable();
            $table->enum('canal', ['web', 'whatsapp', 'telefono'])->default('web');
            $table->enum('estado', ['borrador', 'contactado', 'agendado', 'cerrado', 'perdido'])->default('borrador');
            $table->unsignedInteger('total_min')->default(0);
            $table->unsignedInteger('total_max')->default(0);
            $table->boolean('requiere_visita')->default(false);
            $table->string('utm_source')->nullable();
            $table->string('ip_hash')->nullable();
            $table->timestamps();

            $table->index(['estado', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
