<div class="w-full max-w-sm rounded-2xl border border-line bg-white p-8 shadow-sm">
    <p class="text-lg font-bold text-ink tracking-tight">Mallas Arica Jacob</p>
    <p class="mb-6 text-sm text-ink-soft">Ingresa al panel admin</p>

    @error('throttle')
        <p class="mb-4 rounded-lg bg-cream-deep px-4 py-3 text-sm text-brand-red-dark">{{ $message }}</p>
    @enderror

    <form wire:submit="autenticar" class="space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium text-ink">Correo</label>
            <input
                type="email"
                id="email"
                wire:model="email"
                class="mt-1 w-full rounded-lg border border-line px-3 py-2 text-sm focus:border-brand-red-ui focus:outline-none"
            >
            @error('email')
                <p class="mt-1 text-sm text-brand-red-dark">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-ink">Contraseña</label>
            <input
                type="password"
                id="password"
                wire:model="password"
                class="mt-1 w-full rounded-lg border border-line px-3 py-2 text-sm focus:border-brand-red-ui focus:outline-none"
            >
            @error('password')
                <p class="mt-1 text-sm text-brand-red-dark">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-soft">
            <input type="checkbox" wire:model="remember">
            Recordarme
        </label>

        <button
            type="submit"
            class="w-full rounded-lg bg-brand-red-ui px-4 py-2 text-sm font-semibold text-white hover:bg-brand-red-dark"
        >
            Ingresar
        </button>
    </form>

    <div class="my-4 flex items-center gap-3">
        <div class="h-px flex-1 bg-line"></div>
        <span class="text-xs text-ink-soft">o</span>
        <div class="h-px flex-1 bg-line"></div>
    </div>

    <a
        href="{{ config('app.domain') === 'localhost' ? route('login.google.local') : route('admin.login.google') }}"
        class="flex w-full items-center justify-center gap-2 rounded-lg border border-line px-4 py-2 text-sm font-semibold text-ink transition-colors hover:bg-cream-deep"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="#4285F4" d="M23.52 12.27c0-.85-.08-1.67-.22-2.45H12v4.64h6.47a5.54 5.54 0 0 1-2.4 3.63v3h3.88c2.27-2.09 3.57-5.17 3.57-8.82Z"/>
            <path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.95-2.91l-3.88-3c-1.08.72-2.45 1.15-4.07 1.15-3.13 0-5.78-2.11-6.73-4.96H1.26v3.11A12 12 0 0 0 12 24Z"/>
            <path fill="#FBBC05" d="M5.27 14.28A7.2 7.2 0 0 1 4.89 12c0-.79.14-1.56.38-2.28V6.61H1.26A12 12 0 0 0 0 12c0 1.94.47 3.77 1.26 5.39l4.01-3.11Z"/>
            <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.44-3.44C17.95 1.19 15.24 0 12 0A12 12 0 0 0 1.26 6.61l4.01 3.11C6.22 6.86 8.87 4.75 12 4.75Z"/>
        </svg>
        Iniciar sesión con Google
    </a>
</div>
