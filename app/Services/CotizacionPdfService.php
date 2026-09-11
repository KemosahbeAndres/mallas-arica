<?php

namespace App\Services;

use App\Models\Cotizacion;
use Barryvdh\DomPDF\Facade\Pdf;

class CotizacionPdfService
{
    public function __construct(
        private readonly CotizacionPdfDataBuilder $dataBuilder,
    ) {}

    public function render(Cotizacion $cotizacion): string
    {
        $datos = $this->dataBuilder->construir($cotizacion);

        return Pdf::loadView('pdf.cotizacion', [
            'cotizacion' => $cotizacion,
            ...$datos,
        ])->setPaper('letter')->output();
    }

    public function nombreArchivo(Cotizacion $cotizacion): string
    {
        return "cotizacion-{$cotizacion->folio}.pdf";
    }
}
