<?php

namespace App\Livewire;

use App\Models\Cotizacion;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class SolicitudContacto extends Component
{
    public string $nombre = '';

    public string $telefono = '';

    public string $direccion = '';

    public string $email = '';

    // Honeypot anti-spam: campo invisible que un bot rellenaría.
    public string $sitioWeb = '';

    public ?string $numeroGenerado = null;

    // Máximo de solicitudes que una misma IP puede crear en la ventana de throttle.
    private const THROTTLE_MAX_INTENTOS = 5;

    private const THROTTLE_VENTANA_MINUTOS = 10;

    public function enviar(): void
    {
        $this->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:30'],
            'direccion' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        // Honeypot: si el campo invisible viene relleno, es un bot. Se ignora en silencio.
        if (filled($this->sitioWeb)) {
            return;
        }

        $throttleKey = 'solicitud-contacto:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::THROTTLE_MAX_INTENTOS)) {
            $this->addError('throttle', 'Demasiadas solicitudes seguidas. Espera unos minutos e intenta de nuevo.');

            return;
        }

        RateLimiter::hit($throttleKey, self::THROTTLE_VENTANA_MINUTOS * 60);

        $cotizacion = Cotizacion::create([
            'nombre' => $this->nombre,
            'telefono' => $this->telefono,
            'email' => $this->email ?: null,
            'direccion' => $this->direccion,
            'canal' => 'web',
            'estado' => 'borrador',
            'requiere_visita' => true,
            'ip_hash' => hash('sha256', request()->ip()),
        ]);

        $this->numeroGenerado = $cotizacion->numero;

        $this->reset(['nombre', 'telefono', 'direccion', 'email']);
    }

    public function render()
    {
        return view('livewire.solicitud-contacto');
    }
}
