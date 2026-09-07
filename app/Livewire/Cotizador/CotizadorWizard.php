<?php

namespace App\Livewire\Cotizador;

use App\Jobs\EnviarNotificacionesCotizacion;
use App\Models\Cotizacion;
use App\Models\TipoEspacio;
use App\Models\TipoMalla;
use App\Models\TramoAltura;
use App\Services\CotizacionCalculatorService;
use App\Services\CotizacionPdfService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CotizadorWizard extends Component
{
    /** @var array<int, array{tipo_espacio_id: ?int, tipo_malla_id: ?int, tramo_altura_id: ?int, metros_lineales: ?float}> */
    public array $items = [];

    public string $nombre = '';

    public string $telefono = '';

    public string $direccion = '';

    public string $email = '';

    // Honeypot anti-spam: campo invisible que un bot rellenaría.
    public string $sitioWeb = '';

    public ?string $numeroGenerado = null;

    public ?string $whatsappUrl = null;

    // Máximo de cotizaciones que una misma IP puede crear en la ventana de throttle.
    private const THROTTLE_MAX_INTENTOS = 5;

    private const THROTTLE_VENTANA_MINUTOS = 10;

    public function mount(): void
    {
        $this->items = [$this->itemVacio()];
    }

    private function itemVacio(): array
    {
        return [
            'tipo_espacio_id' => null,
            'tipo_malla_id' => null,
            'tramo_altura_id' => null,
            'metros_lineales' => null,
        ];
    }

    public function agregarItem(): void
    {
        $this->items[] = $this->itemVacio();
    }

    public function quitarItem(int $index): void
    {
        if (count($this->items) <= 1) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    #[Computed]
    public function tiposEspacio()
    {
        return TipoEspacio::query()->where('activo', true)->orderBy('orden')->get();
    }

    #[Computed]
    public function tramosAltura()
    {
        return TramoAltura::query()->orderBy('orden')->get();
    }

    #[Computed]
    public function tiposMalla()
    {
        return TipoMalla::query()->where('activo', true)->get();
    }

    private function itemsValidos(): array
    {
        return array_values(array_filter(
            $this->items,
            fn (array $item) => filled($item['tipo_espacio_id'] ?? null)
                && filled($item['metros_lineales'] ?? null)
                && (float) $item['metros_lineales'] > 0,
        ));
    }

    #[Computed]
    public function puedeEnviar(): bool
    {
        return filled($this->nombre) && filled($this->telefono) && $this->itemsValidos() !== [];
    }

    #[On('solicitar-envio-whatsapp')]
    public function crearCotizacionYAbrirWhatsapp(): void
    {
        $cotizacion = $this->persistirCotizacion();

        if (! $cotizacion) {
            return;
        }

        $this->numeroGenerado = $cotizacion->numero;
        $this->whatsappUrl = $this->construirUrlWhatsapp($cotizacion, (bool) $cotizacion->requiere_visita);

        $this->js('window.open('.json_encode($this->whatsappUrl).", '_blank')");
    }

    #[On('solicitar-descarga-pdf')]
    public function crearCotizacionYDescargarPdf()
    {
        $cotizacion = $this->persistirCotizacion();

        if (! $cotizacion) {
            return;
        }

        $this->numeroGenerado = $cotizacion->numero;

        $pdfService = app(CotizacionPdfService::class);

        return response()->streamDownload(
            fn () => print ($pdfService->render($cotizacion)),
            $pdfService->nombreArchivo($cotizacion),
        );
    }

    private function persistirCotizacion(): ?Cotizacion
    {
        $this->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        // Honeypot: si el campo invisible viene relleno, es un bot. Se ignora en silencio.
        if (filled($this->sitioWeb)) {
            return null;
        }

        $throttleKey = 'cotizador:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::THROTTLE_MAX_INTENTOS)) {
            $this->addError('throttle', 'Demasiadas cotizaciones seguidas. Espera unos minutos e intenta de nuevo.');

            return null;
        }

        RateLimiter::hit($throttleKey, self::THROTTLE_VENTANA_MINUTOS * 60);

        $itemsValidos = $this->itemsValidos();

        if ($itemsValidos === []) {
            return null;
        }

        $resultado = app(CotizacionCalculatorService::class)->calcularCotizacion($itemsValidos);

        $cotizacion = Cotizacion::create([
            'nombre' => $this->nombre,
            'telefono' => $this->telefono,
            'email' => $this->email ?: null,
            'direccion' => $this->direccion ?: null,
            'canal' => 'web',
            'estado' => 'borrador',
            'total_min' => $resultado->totalMin,
            'total_max' => $resultado->totalMax,
            'requiere_visita' => $resultado->requiereVisita,
            'ip_hash' => hash('sha256', request()->ip()),
        ]);

        foreach ($resultado->items as $item) {
            $cotizacion->items()->create([
                'tipo_espacio_id' => $item->tipoEspacioId,
                'tipo_malla_id' => $item->tipoMallaId,
                'tramo_altura_id' => $item->tramoAlturaId,
                'metros_lineales' => $item->metrosLineales,
                'precio_ml_min_snapshot' => $item->precioMlMinSnapshot,
                'precio_ml_max_snapshot' => $item->precioMlMaxSnapshot,
                'multiplicador_snapshot' => $item->multiplicadorSnapshot,
                'subtotal_min' => $item->subtotalMin,
                'subtotal_max' => $item->subtotalMax,
            ]);
        }

        $this->notificarCotizacion($cotizacion);

        return $cotizacion;
    }

    private function notificarCotizacion(Cotizacion $cotizacion): void
    {
        // El correo es un efecto secundario: si el dispatch falla (Redis caído),
        // la cotización ya está persistida y el flujo de conversión sigue intacto.
        try {
            EnviarNotificacionesCotizacion::dispatch($cotizacion)->afterResponse();
        } catch (\Throwable $e) {
            Log::error('No se pudo encolar la notificación de cotización', [
                'cotizacion_id' => $cotizacion->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function construirUrlWhatsapp(Cotizacion $cotizacion, bool $requiereVisita): string
    {
        $telefonoEmpresa = '56986455205';

        $mensaje = $requiereVisita
            ? "Hola, quiero coordinar una visita técnica para mi cotización N° {$cotizacion->numero}. Mi nombre es {$this->nombre}."
            : "Hola, quiero avanzar con mi cotización N° {$cotizacion->numero} (rango \${$this->numeroFormateado($cotizacion->total_min)} - \${$this->numeroFormateado($cotizacion->total_max)}). Mi nombre es {$this->nombre}.";

        return "https://wa.me/{$telefonoEmpresa}?text=".rawurlencode($mensaje);
    }

    private function numeroFormateado(int $valor): string
    {
        return number_format($valor, 0, ',', '.');
    }

    public function render()
    {
        return view('livewire.cotizador.cotizador-wizard');
    }
}
