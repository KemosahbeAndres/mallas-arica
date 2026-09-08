<?php

namespace App\Livewire;

use App\Jobs\NotificarNuevoCliente;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    public bool $enviado = false;

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

        $cliente = DB::transaction(function () {
            // Se busca por teléfono para no duplicar si el visitante reenvía.
            $cliente = Cliente::firstOrNew(['telefono' => $this->telefono]);
            $cliente->fill([
                'nombre' => $this->nombre,
                'email' => $this->email ?: $cliente->email,
            ])->save();

            $direccion = trim($this->direccion);
            $yaTiene = $cliente->direcciones()
                ->whereRaw('LOWER(direccion) = ?', [mb_strtolower($direccion)])
                ->exists();

            if (! $yaTiene) {
                $cliente->direcciones()->create(['direccion' => $direccion]);
            }

            return $cliente;
        });

        $this->enviado = true;
        $this->reset(['nombre', 'telefono', 'direccion', 'email']);

        // El correo es un efecto secundario: si el dispatch falla (Redis caído),
        // el cliente ya está persistido y el flujo sigue intacto.
        try {
            NotificarNuevoCliente::dispatch($cliente)->afterResponse();
        } catch (\Throwable $e) {
            Log::error('No se pudo encolar la notificación de nuevo cliente', [
                'cliente_id' => $cliente->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.solicitud-contacto');
    }
}
