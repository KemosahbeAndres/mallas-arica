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
        Schema::create('media_items', function (Blueprint $table) {
            $table->id();
            $table->string('archivo_path');
            $table->string('titulo')->nullable();
            $table->foreignId('media_album_id')->nullable()->constrained('media_albums')->nullOnDelete();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['media_album_id', 'orden']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_items');
    }
};
