<?php

namespace App\Livewire\Admin\Ajustes;

use App\Services\GoogleOAuthConfig;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Ajustes → Login con Google (solo super_admin). Sube el JSON tal cual lo
 * entrega Google Cloud Console para un cliente OAuth "Web application" —
 * las credenciales se guardan en la tabla `configuraciones`, no en .env
 * (ver GoogleOAuthConfig).
 */
class GoogleLoginSettings extends Component
{
    use WithFileUploads;

    public $archivo = null;

    public ?string $guardado = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->esSuperAdmin(), 403);
    }

    public function guardar(GoogleOAuthConfig $config): void
    {
        $this->validate([
            'archivo' => ['required', 'file', 'extensions:json', 'max:64'],
        ]);

        try {
            $config->guardarDesdeJson(file_get_contents($this->archivo->getRealPath()));
        } catch (InvalidArgumentException $e) {
            $this->addError('archivo', $e->getMessage());

            return;
        }

        $this->reset('archivo');
        $this->guardado = 'Credenciales de Google guardadas.';
    }

    public function eliminar(GoogleOAuthConfig $config): void
    {
        $config->eliminar();
        $this->guardado = 'Se quitó la configuración de Google.';
    }

    public function render()
    {
        return view('livewire.admin.ajustes.google-login-settings', [
            'config' => app(GoogleOAuthConfig::class),
        ])->layout('components.layouts.admin', [
            'title' => 'Login con Google',
            'subtitle' => 'Ajustes › Login con Google',
        ]);
    }
}
