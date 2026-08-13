<?php

namespace Database\Seeders;

use App\Models\TipoMalla;
use Illuminate\Database\Seeder;

class TipoMallaSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'slug' => 'estandar',
                'nombre' => 'Estándar',
                'grosor_mm' => 0.90,
                'rombo_cm' => 5.00,
                'multiplicador' => 1.00,
            ],
            [
                'slug' => 'reforzada-mascotas',
                'nombre' => 'Reforzada mascotas',
                'grosor_mm' => 1.00,
                'rombo_cm' => 1.50,
                // TODO: multiplicador exacto pendiente de confirmar (CLAUDE.md §9.2). Placeholder ~1.35.
                'multiplicador' => 1.35,
            ],
        ];

        foreach ($tipos as $tipo) {
            TipoMalla::updateOrCreate(['slug' => $tipo['slug']], $tipo);
        }
    }
}
