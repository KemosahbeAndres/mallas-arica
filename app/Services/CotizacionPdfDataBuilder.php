<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Support\FechaEsp;
use Carbon\Carbon;

class CotizacionPdfDataBuilder
{
    public const IVA_TASA = 0.19;

    public const MENSAJE_VIGENCIA_DEFAULT = 'Esta cotización tiene una vigencia de 10 días a contar de la fecha de emisión. Los valores están expresados en pesos chilenos (CLP) e incluyen IVA según se detalla.';

    public const EMPRESA = [
        'rut' => '10.610.838-2',
        'direccion' => 'Av. Diego Portales #1333, Arica',
        'telefono' => '+56 9 8645 5205',
        'email' => 'contacto@mallasarica.cl',
    ];

    public function __construct(private readonly SiteContentService $siteContent) {}

    /**
     * @return array{numero: string, fecha: string, empresa: array, lineas: array, neto: int, iva: int, total: int, mensajeVigencia: string}
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
            'numero' => $cotizacion->numero,
            'fecha' => $this->formatearFecha($cotizacion->created_at),
            'empresa' => self::EMPRESA,
            'lineas' => $lineas,
            'neto' => $neto,
            'iva' => $iva,
            'total' => $neto + $iva,
            'mensajeVigencia' => $this->siteContent->get('cotizaciones.mensaje_vigencia', self::MENSAJE_VIGENCIA_DEFAULT),
        ];
    }

    private function formatearFecha(Carbon $fecha): string
    {
        return FechaEsp::largo($fecha);
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
