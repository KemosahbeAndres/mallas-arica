<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    private const THROTTLE_MAX_INTENTOS = 5;

    private const THROTTLE_VENTANA_MINUTOS = 10;

    public function autenticar(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = 'admin-login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::THROTTLE_MAX_INTENTOS)) {
            $this->addError('throttle', 'Demasiados intentos. Espera unos minutos e intenta de nuevo.');

            return;
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey, self::THROTTLE_VENTANA_MINUTOS * 60);
            $this->addError('email', 'Credenciales incorrectas.');

            return;
        }

        RateLimiter::clear($throttleKey);
        session()->regenerate();

        $this->redirect(route('admin.tarifas'), navigate: false);
    }

    public function render()
    {
        return view('livewire.admin.auth.login')->layout('components.layouts.admin', ['title' => 'Ingresar']);
    }
}
