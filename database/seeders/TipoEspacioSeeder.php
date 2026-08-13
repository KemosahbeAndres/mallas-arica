<?php

namespace Database\Seeders;

use App\Models\TipoEspacio;
use Illuminate\Database\Seeder;

class TipoEspacioSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['slug' => 'ventana', 'nombre' => 'Ventana', 'permite_calculo' => true, 'orden' => 1],
            ['slug' => 'balcon', 'nombre' => 'Balcón', 'permite_calculo' => true, 'orden' => 2],
            ['slug' => 'terraza', 'nombre' => 'Terraza', 'permite_calculo' => true, 'orden' => 3],
            ['slug' => 'escalera', 'nombre' => 'Escalera', 'permite_calculo' => true, 'orden' => 4],
            ['slug' => 'mascotas', 'nombre' => 'Mascotas', 'permite_calculo' => true, 'orden' => 5],
            ['slug' => 'piscina', 'nombre' => 'Piscina', 'permite_calculo' => false, 'orden' => 6],
        ];

        foreach ($tipos as $tipo) {
            TipoEspacio::updateOrCreate(['slug' => $tipo['slug']], $tipo);
        }
    }
}
