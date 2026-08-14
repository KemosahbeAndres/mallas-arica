<?php

namespace App\Livewire\Cotizador;

use App\DataTransferObjects\CotizacionCalculoResultado;
use App\Services\CotizacionCalculatorService;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class PanelPrecio extends Component
{
    /** @var array<int, array{tipo_espacio_id: ?int, tipo_malla_id: ?int, tramo_altura_id: ?int, metros_lineales: ?float}> */
    #[Reactive]
    public array $items = [];

    #[Reactive]
    public bool $puedeEnviar = false;

    public function calculo(): ?CotizacionCalculoResultado
    {
        $itemsValidos = array_values(array_filter(
            $this->items,
            fn (array $item) => filled($item['tipo_espacio_id'] ?? null)
                && filled($item['metros_lineales'] ?? null)
                && (float) $item['metros_lineales'] > 0,
        ));

        if ($itemsValidos === []) {
            return null;
        }

        return app(CotizacionCalculatorService::class)->calcularCotizacion($itemsValidos);
    }

    public function crearPorWhatsapp(): void
    {
        $this->dispatch('solicitar-envio-whatsapp')->to(CotizadorWizard::class);
    }

    public function descargarPdf(): void
    {
        $this->dispatch('solicitar-descarga-pdf')->to(CotizadorWizard::class);
    }

    public function render()
    {
        return view('livewire.cotizador.panel-precio', [
            'resultado' => $this->calculo(),
        ]);
    }
}
