<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\CotizacionItem;

class CotizacionPdfDataBuilder
{
    public const IVA_TASA = 0.19;

    public const EMPRESA = [
        'rut' => '10.610.838-2',
        'direccion' => 'Av. Diego Portales #1333, Arica',
        'telefono' => '+56 9 8645 5205',
        'email' => 'jacobtj1992@gmail.com',
    ];

    private const MESES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    /**
     * @return array{numero: string, fecha: string, empresa: array, lineas: array, neto: int, iva: int, total: int}
     */
    public function construir(Cotizacion $cotizacion): array
    {
        $cotizacion->loadMissing('items.tipoEspacio', 'items.tipoMalla', 'items.tramoAltura');

        $lineas = $cotizacion->items->map(fn (CotizacionItem $item) => $this->construirLinea($item))->all();

        $neto = array_sum(array_map(
            fn (array $linea) => $linea['pendiente'] ? 0 : $linea['subtotal'],
            $lineas,
        ));

        $iva = (int) round($neto * self::IVA_TASA);

        return [
            'numero' => str_pad((string) $cotizacion->id, 4, '0', STR_PAD_LEFT),
            'fecha' => $this->formatearFecha($cotizacion->created_at),
            'empresa' => self::EMPRESA,
            'lineas' => $lineas,
            'neto' => $neto,
            'iva' => $iva,
            'total' => $neto + $iva,
        ];
    }

    private function formatearFecha(\Carbon\Carbon $fecha): string
    {
        return "{$fecha->day} de ".self::MESES[$fecha->month]." de {$fecha->year}";
    }

    private function construirLinea(CotizacionItem $item): array
    {
        $pendiente = $item->precio_ml_max_snapshot === null;

        $descripcion = $this->construirDescripcion($item);

        if ($pendiente) {
            return [
                'descripcion' => $descripcion,
                'pendiente' => true,
                'precioUnitario' => null,
                'cantidad' => null,
                'subtotal' => null,
            ];
        }

        $precioUnitario = (int) round($item->precio_ml_max_snapshot * (float) $item->multiplicador_snapshot);
        $cantidad = (float) $item->metros_lineales;
        $subtotal = (int) round($precioUnitario * $cantidad);

        return [
            'descripcion' => $descripcion,
            'pendiente' => false,
            'precioUnitario' => $precioUnitario,
            'cantidad' => $cantidad,
            'subtotal' => $subtotal,
        ];
    }

    private function construirDescripcion(CotizacionItem $item): string
    {
        $espacio = mb_strtolower($item->tipoEspacio?->nombre ?? 'espacio');

        $mallaSlug = $item->tipoMalla?->slug;
        $prefijoMalla = $mallaSlug && $mallaSlug !== 'estandar'
            ? "Malla {$item->tipoMalla->nombre} — "
            : 'Malla de protección estándar — ';

        $tramo = $item->tramoAltura?->etiqueta;

        return $prefijoMalla."instalación en {$espacio}".($tramo ? ", {$tramo}" : '');
    }
}
