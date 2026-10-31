<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Empresas emisoras de cotizaciones. Antes los datos de la empresa estaban
 * hardcodeados en CotizacionPdfDataBuilder::EMPRESA; ahora el dueño puede
 * tener varias y elegir cuál emite cada cotización.
 *
 * Se siembra la empresa actual como predeterminada y se asigna a las
 * cotizaciones existentes, para que sus PDF no cambien si luego se edita o
 * se cambia la predeterminada.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Cada paso comprueba si ya existe: MariaDB no hace rollback de DDL, así
        // que si algo fallara a medias, volver a correr la migración la completa
        // sin romper ni duplicar nada. Solo agrega; nunca borra datos.
        if (! Schema::hasTable('empresas')) {
            Schema::create('empresas', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('subtitulo')->nullable();
                $table->string('rut', 20)->nullable();
                $table->string('direccion')->nullable();
                $table->string('telefono', 40)->nullable();
                $table->string('email')->nullable();
                $table->boolean('predeterminada')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('cotizaciones', 'empresa_id')) {
            Schema::table('cotizaciones', function (Blueprint $table) {
                $table->foreignId('empresa_id')
                    ->nullable()
                    ->after('cliente_direccion_id')
                    ->constrained('empresas')
                    ->nullOnDelete();
            });
        }

        $empresaId = DB::table('empresas')->where('predeterminada', true)->value('id')
            ?? DB::table('empresas')->insertGetId([
                'nombre' => 'Mallas Arica Jacob',
                'subtitulo' => 'Instalación de mallas de protección · Arica',
                'rut' => '10.610.838-2',
                'direccion' => 'Av. Diego Portales #1333, Arica',
                'telefono' => '+56 9 8645 5205',
                'email' => 'contacto@mallasarica.cl',
                'predeterminada' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        // Solo las cotizaciones sin empresa; nunca pisa una ya asignada.
        DB::table('cotizaciones')->whereNull('empresa_id')->update(['empresa_id' => $empresaId]);
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropColumn('empresa_id');
        });

        Schema::dropIfExists('empresas');
    }
};
