<?php

namespace App\Livewire\Admin\Ajustes;

use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Página única «Ajustes» con submenú vertical (sidebar interno): Perfil,
 * Apariencia (próximamente) y Google SSO (solo super_admin). Accesible desde
 * el dropdown de usuario del navbar, no desde la navbar horizontal.
 */
class AjustesPanel extends Component
{
    #[Url]
    public string $tab = 'perfil';

    public function mount(): void
    {
        if ($this->tab === 'google' && ! auth()->user()->esSuperAdmin()) {
            $this->tab = 'perfil';
        }
    }

    public function updatedTab(string $value): void
    {
        $valido = in_array($value, ['perfil', 'apariencia'], true)
            || ($value === 'google' && auth()->user()->esSuperAdmin());

        if (! $valido) {
            $this->tab = 'perfil';
        }
    }

    public function render()
    {
        return view('livewire.admin.ajustes.ajustes-panel')
            ->layout('components.layouts.admin', [
                'title' => 'Ajustes',
                'subtitle' => 'Tu cuenta y la configuración del panel',
            ]);
    }
}
