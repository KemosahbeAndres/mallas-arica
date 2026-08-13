<?php

namespace Database\Seeders;

use App\Models\Tarifa;
use App\Models\TipoEspacio;
use App\Models\TramoAltura;
use Illuminate\Database\Seeder;

class TarifaSeeder extends Seeder
{
    /**
     * TODO: valores placeholder. Tarifas reales pendientes de confirmar (CLAUDE.md §9.1).
     * No se cargan tarifas para tramos con requiere_visita=true ni para tipos con permite_calculo=false.
     */
    public function run(): void
    {
        $precioBaseMin = [
            'ventana' => 8000,
            'balcon' => 9000,
            'terraza' => 9500,
            'escalera' => 10000,
            'mascotas' => 8500,
        ];

        $incrementoPorTramo = [1 => 0, 2 => 1000, 3 => 2500];

        $tramos = TramoAltura::where('requiere_visita', false)->orderBy('orden')->get();

        foreach (TipoEspacio::where('permite_calculo', true)->get() as $tipoEspacio) {
            $base = $precioBaseMin[$tipoEspacio->slug] ?? 8000;

            foreach ($tramos as $tramo) {
                $min = $base + $incrementoPorTramo[$tramo->orden];
                $max = (int) round($min * 1.18);

                Tarifa::updateOrCreate(
                    [
                        'tipo_espacio_id' => $tipoEspacio->id,
                        'tramo_altura_id' => $tramo->id,
                        'vigente_desde' => now()->toDateString(),
                    ],
                    [
                        'precio_ml_min' => $min,
                        'precio_ml_max' => $max,
                        'vigente_hasta' => null,
                    ]
                );
            }
        }
    }
}
