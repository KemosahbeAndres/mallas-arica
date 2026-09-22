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
        Schema::table('trabajos', function (Blueprint $table) {
            $table->unsignedSmallInteger('cantidad_ventanas')->default(0)->after('descripcion');
            $table->unsignedSmallInteger('cantidad_balcones')->default(0)->after('cantidad_ventanas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajos', function (Blueprint $table) {
            $table->dropColumn(['cantidad_ventanas', 'cantidad_balcones']);
        });
    }
};
