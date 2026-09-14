<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Support\FechaEsp;

class CotizacionPdfDataBuilder
{
    public const MENSAJE_VIGENCIA_DEFAULT = 'Esta cotización tiene una vigencia de 10 días a contar de la fecha de emisión. Los valores están expresados en pesos chilenos (CLP) e incluyen IVA según se detalla.';

    public const EMPRESA = [
        'rut' => '10.610.838-2',
        'direccion' => 'Av. Diego Portales #1333, Arica',
        'telefono' => '+56 9 8645 5205',
        'email' => 'contacto@mallasarica.cl',
    ];

    public function __construct(private readonly SiteContentService $siteContent) {}

    /**
     * @return array{folio: string, fecha: string, empresa: array, cliente: array, lineas: array, neto: int, iva: int, total: int, descuentoPct: float, mensajeVigencia: string}
     */
    public function construir(Cotizacion $cotizacion): array
    {
        $cotizacion->loadMissing('items', 'cliente', 'clienteDireccion');

        $lineas = $cotizacion->items->map(fn (CotizacionItem $item) => [
            'descripcion' => $item->descripcion,
            'precioUnitario' => (int) $item->precio_unitario,
            'cantidad' => (float) $item->cantidad,
            'descuentoPct' => (float) $item->descuento_pct,
            'subtotal' => (int) $item->subtotal,
        ])->all();

        return [
            'folio' => $cotizacion->folio,
            'fecha' => FechaEsp::largo($cotizacion->created_at),
            'empresa' => self::EMPRESA,
            'cliente' => $this->datosCliente($cotizacion),
            'lineas' => $lineas,
            'descuentoPct' => (float) $cotizacion->descuento_pct,
            'neto' => $cotizacion->neto,
            'iva' => $cotizacion->iva,
            'total' => $cotizacion->total,
            'mensajeVigencia' => $this->siteContent->get('cotizaciones.mensaje_vigencia', self::MENSAJE_VIGENCIA_DEFAULT),
        ];
    }

    /**
     * @return array{nombre: string, direccion: string, contacto: string}
     */
    private function datosCliente(Cotizacion $cotizacion): array
    {
        $cliente = $cotizacion->cliente;

        $nombre = $cliente?->nombre ?? $cotizacion->nombre ?? 'Cliente';
        $telefono = $cliente?->telefono ?? $cotizacion->telefono ?? '';
        $email = $cliente?->email ?? $cotizacion->email ?? '';
        $direccion = $cotizacion->clienteDireccion?->direccion ?? $cotizacion->direccion ?? 'Arica';

        return [
            'nombre' => $nombre,
            'direccion' => $direccion,
            'contacto' => trim($telefono.($email ? ' · '.$email : ''), ' ·'),
        ];
    }
}
