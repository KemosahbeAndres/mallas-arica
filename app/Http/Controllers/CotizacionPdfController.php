<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Services\CotizacionPdfService;
use Symfony\Component\HttpFoundation\Response;

class CotizacionPdfController extends Controller
{
    public function descargar(Cotizacion $cotizacion, CotizacionPdfService $pdf): Response
    {
        return response($pdf->render($cotizacion), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$pdf->nombreArchivo($cotizacion).'"',
        ]);
    }
}
