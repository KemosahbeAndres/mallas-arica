<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Se pone a la fecha del aviso al dueño (job NotificarNuevoCliente),
            // solo para los clientes que entran por el formulario del sitio.
            $table->timestamp('notificado_at')->nullable()->after('notas');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('notificado_at');
        });
    }
};
