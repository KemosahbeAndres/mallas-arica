<?php

namespace App\Services;

use App\DataTransferObjects\CotizacionCalculoResultado;
use App\DataTransferObjects\ItemCalculoResultado;
use App\Models\Tarifa;
use App\Models\TipoEspacio;
use App\Models\TipoMalla;
use App\Models\TramoAltura;

class CotizacionCalculatorService
{
    public const METRAJE_MINIMO = 2.0;

    public const METRAJE_MAXIMO = 40.0;

    /**
     * Calcula un ítem de cotización a partir de sus datos crudos.
     *
     * No lanza excepciones ante combinaciones no cotizables: siempre devuelve
     * un ItemCalculoResultado, marcado como lead cuando no corresponde precio
     * automático (CLAUDE.md §4.3).
     */
    public function calcularItem(
        int $tipoEspacioId,
        ?int $tipoMallaId,
        ?int $tramoAlturaId,
        float $metrosLineales,
    ): ItemCalculoResultado {
        $tipoEspacio = TipoEspacio::find($tipoEspacioId);

        if (! $tipoEspacio || ! $tipoEspacio->permite_calculo) {
            return $this->itemLead($tipoEspacioId, $tipoMallaId, $tramoAlturaId, $metrosLineales, 'tipo_sin_calculo_automatico');
        }

        if ($metrosLineales <= 0) {
            return $this->itemLead($tipoEspacioId, $tipoMallaId, $tramoAlturaId, $metrosLineales, 'metraje_invalido');
        }

        if ($metrosLineales > self::METRAJE_MAXIMO) {
            return $this->itemLead($tipoEspacioId, $tipoMallaId, $tramoAlturaId, $metrosLineales, 'metraje_excede_maximo');
        }

        $tramoAltura = $tramoAlturaId ? TramoAltura::find($tramoAlturaId) : null;

        if (! $tramoAltura) {
            return $this->itemLead($tipoEspacioId, $tipoMallaId, $tramoAlturaId, $metrosLineales, 'tramo_altura_invalido');
        }

        if ($tramoAltura->requiere_visita) {
            return $this->itemLead($tipoEspacioId, $tipoMallaId, $tramoAlturaId, $metrosLineales, 'altura_requiere_visita');
        }

        $tarifa = Tarifa::where('tipo_espacio_id', $tipoEspacioId)
            ->where('tramo_altura_id', $tramoAlturaId)
            ->where('vigente_desde', '<=', now()->toDateString())
            ->where(function ($query) {
                $query->whereNull('vigente_hasta')
                    ->orWhere('vigente_hasta', '>=', now()->toDateString());
            })
            ->orderByDesc('vigente_desde')
            ->first();

        if (! $tarifa) {
            return $this->itemLead($tipoEspacioId, $tipoMallaId, $tramoAlturaId, $metrosLineales, 'sin_tarifa_vigente');
        }

        $multiplicador = 1.0;
        if ($tipoMallaId) {
            $multiplicador = (float) (TipoMalla::find($tipoMallaId)?->multiplicador ?? 1.0);
        }

        $metrosFacturables = max($metrosLineales, self::METRAJE_MINIMO);

        $subtotalMin = $this->redondearAlMillar($metrosFacturables * $tarifa->precio_ml_min * $multiplicador);
        $subtotalMax = $this->redondearAlMillar($metrosFacturables * $tarifa->precio_ml_max * $multiplicador);

        return new ItemCalculoResultado(
            tipoEspacioId: $tipoEspacioId,
            tipoMallaId: $tipoMallaId,
            tramoAlturaId: $tramoAlturaId,
            metrosLineales: $metrosLineales,
            requiereVisita: false,
            esLead: false,
            precioMlMinSnapshot: $tarifa->precio_ml_min,
            precioMlMaxSnapshot: $tarifa->precio_ml_max,
            multiplicadorSnapshot: $multiplicador,
            subtotalMin: $subtotalMin,
            subtotalMax: $subtotalMax,
        );
    }

    /**
     * @param  array<int, array{tipo_espacio_id: int, tipo_malla_id: ?int, tramo_altura_id: ?int, metros_lineales: float}>  $items
     */
    public function calcularCotizacion(array $items): CotizacionCalculoResultado
    {
        $resultados = array_map(
            fn (array $item) => $this->calcularItem(
                $item['tipo_espacio_id'],
                $item['tipo_malla_id'] ?? null,
                $item['tramo_altura_id'] ?? null,
                (float) $item['metros_lineales'],
            ),
            $items,
        );

        $totalMin = array_sum(array_map(fn (ItemCalculoResultado $r) => $r->subtotalMin, $resultados));
        $totalMax = array_sum(array_map(fn (ItemCalculoResultado $r) => $r->subtotalMax, $resultados));
        $requiereVisita = count(array_filter($resultados, fn (ItemCalculoResultado $r) => $r->esLead)) > 0;

        return new CotizacionCalculoResultado(
            items: $resultados,
            totalMin: $totalMin,
            totalMax: $totalMax,
            requiereVisita: $requiereVisita,
        );
    }

    private function itemLead(
        int $tipoEspacioId,
        ?int $tipoMallaId,
        ?int $tramoAlturaId,
        float $metrosLineales,
        string $motivo,
    ): ItemCalculoResultado {
        return new ItemCalculoResultado(
            tipoEspacioId: $tipoEspacioId,
            tipoMallaId: $tipoMallaId,
            tramoAlturaId: $tramoAlturaId,
            metrosLineales: $metrosLineales,
            requiereVisita: true,
            esLead: true,
            motivoLead: $motivo,
        );
    }

    private function redondearAlMillar(float $valor): int
    {
        return (int) (ceil($valor / 1000) * 1000);
    }
}
