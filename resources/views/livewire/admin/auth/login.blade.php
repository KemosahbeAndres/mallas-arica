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
</div>
