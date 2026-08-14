<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('cotizacion_items', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('visitas', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('cotizacion_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('visitas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
