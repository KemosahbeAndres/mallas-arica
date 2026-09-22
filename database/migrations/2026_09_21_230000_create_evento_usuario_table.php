<?php

use App\Models\Evento;
use App\Models\Trabajo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('evento_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')->constrained('eventos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['evento_id', 'user_id']);
        });

        // Backfill: todo evento que ya viene de una OT hereda como asignados
        // a los colaboradores actuales de esa OT (CLAUDE.md §11 ter/octies —
        // trabajos.evento_id → eventos). Eventos sueltos sin OT quedan sin
        // usuario hasta que alguien los edite y asigne uno a mano; el panel
        // ya no permite crear eventos nuevos sin al menos un usuario.
        Trabajo::query()
            ->whereNotNull('evento_id')
            ->with('colaboradores:id')
            ->each(function (Trabajo $trabajo) {
                $filas = $trabajo->colaboradores->map(fn ($colaborador) => [
                    'evento_id' => $trabajo->evento_id,
                    'user_id' => $colaborador->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

                if ($filas !== []) {
                    DB::table('evento_usuario')->insertOrIgnore($filas);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evento_usuario');
    }
};
