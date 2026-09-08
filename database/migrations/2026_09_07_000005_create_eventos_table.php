<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('tipo')->default('terreno');   // terreno | oficina
            $table->string('estado')->default('agendado'); // agendado | hecho | cancelado
            $table->dateTime('inicio');
            $table->dateTime('fin')->nullable();
            $table->boolean('todo_el_dia')->default(false);
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('ubicacion')->nullable();
            $table->text('notas')->nullable();

            // Preparado para el sync con Google Calendar (sprint posterior).
            // Se declara ya para no re-migrar la tabla; sin uso todavía.
            $table->string('google_event_id')->nullable()->unique();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['inicio', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};
