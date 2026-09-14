<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 12 — rediseño de Cotizaciones a CRM manual.
 *
 * - El cotizador público con cálculo automático se retira (CLAUDE.md §4). Ya no
 *   hay leads del sitio como "cotización borrador": el formulario público crea
 *   un Cliente.
 * - Estados: borrador → generada → aceptada → rechazada (remapeo de los viejos).
 * - `cotizaciones` gana cliente_id / cliente_direccion_id / descuento_pct y
 *   pierde las columnas del flujo público (uuid, canal, requiere_visita,
 *   utm_source, ip_hash).
 * - `cotizacion_items` pasa a líneas libres: descripcion + precio_unitario +
 *   cantidad + descuento_pct + subtotal; se van los snapshots de tarifa y las
 *   FKs a los catálogos (que este mismo sprint elimina).
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- cotizaciones: remapeo de estados antes de cambiar el enum ---
        DB::table('cotizaciones')->where('estado', 'contactado')->update(['estado' => 'generada']);
        DB::table('cotizaciones')->whereIn('estado', ['agendado', 'cerrado'])->update(['estado' => 'aceptada']);
        DB::table('cotizaciones')->where('estado', 'perdido')->update(['estado' => 'rechazada']);

        // MariaDB/MySQL: redefinir el enum con SQL crudo (Doctrine no lo maneja).
        // SQLite (tests) guarda enum como texto y no valida el CHECK, así que
        // basta con el remapeo de datos de arriba.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE cotizaciones MODIFY estado ENUM('borrador','generada','aceptada','rechazada') NOT NULL DEFAULT 'borrador'");
        }

        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('id')->constrained('clientes')->nullOnDelete();
            $table->foreignId('cliente_direccion_id')->nullable()->after('cliente_id')->constrained('cliente_direcciones')->nullOnDelete();
            $table->decimal('descuento_pct', 5, 2)->default(0)->after('total_max');

            $table->dropUnique('cotizaciones_uuid_unique');
            $table->dropColumn(['uuid', 'canal', 'requiere_visita', 'utm_source', 'ip_hash']);
        });

        // El contacto vive ahora en `clientes`; los de `cotizaciones` son solo
        // una copia de conveniencia (para el PDF de cotizaciones sin cliente).
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->string('nombre')->nullable()->change();
            $table->string('telefono')->nullable()->change();
        });

        // --- cotizacion_items: a líneas libres ---
        Schema::table('cotizacion_items', function (Blueprint $table) {
            $table->string('descripcion')->after('cotizacion_id');
            $table->unsignedInteger('precio_unitario')->default(0)->after('descripcion');
            $table->decimal('cantidad', 8, 2)->default(1)->after('precio_unitario');
            $table->decimal('descuento_pct', 5, 2)->default(0)->after('cantidad');
            $table->unsignedInteger('subtotal')->default(0)->after('descuento_pct');
        });

        Schema::table('cotizacion_items', function (Blueprint $table) {
            $table->dropForeign(['tipo_espacio_id']);
            $table->dropForeign(['tipo_malla_id']);
            $table->dropForeign(['tramo_altura_id']);
            $table->dropColumn([
                'tipo_espacio_id', 'tipo_malla_id', 'tramo_altura_id',
                'metros_lineales', 'precio_ml_min_snapshot', 'precio_ml_max_snapshot',
                'multiplicador_snapshot', 'subtotal_min', 'subtotal_max',
            ]);
        });

        // --- galeria_items: quitar la FK al catálogo que se elimina ---
        Schema::table('galeria_items', function (Blueprint $table) {
            $table->dropForeign(['tipo_espacio_id']);
            $table->dropColumn('tipo_espacio_id');
        });

        // --- eliminar el motor de cálculo ---
        Schema::dropIfExists('tarifas');
        Schema::dropIfExists('tramos_altura');
        Schema::dropIfExists('tipos_malla');
        Schema::dropIfExists('tipos_espacio');

        // `visitas` nunca tuvo UI y su rol lo cumple ahora `eventos` (Sprint 10).
        Schema::dropIfExists('visitas');
    }

    public function down(): void
    {
        // Rediseño destructivo: no hay vuelta atrás automática. Para revertir,
        // restaurar desde un backup previo a la migración.
        throw new RuntimeException('Migración no reversible: rediseño de Cotizaciones (Sprint 12).');
    }
};
