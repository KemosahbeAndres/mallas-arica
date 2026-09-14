<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Un lead que llega del sitio se podrá "convertir en cliente" en el
            // sprint de Cotizaciones (el último). El matcheo será por teléfono.
            $table->index('telefono');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
