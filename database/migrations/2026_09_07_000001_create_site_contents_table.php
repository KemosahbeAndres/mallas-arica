<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();          // ej. hero.titulo, nosotros.texto_1, cotizaciones.mensaje_vigencia
            $table->text('value')->nullable();         // contenido actual editable
            $table->string('grupo');                   // hero | nosotros | cotizaciones — agrupa la UI del panel
            $table->string('label');                   // etiqueta visible para el dueño
            $table->string('tipo')->default('text');   // text | textarea — cómo renderizar el input
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->index('grupo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_contents');
    }
};
