<div>
    <div class="border-line rounded-lg border bg-white px-4 py-4">
        @if ($guardado)
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm font-medium text-green-800">
                {{ $guardado }}
            </div>
        @endif

        <form wire:submit="guardar" class="flex flex-col gap-4">
            <div class="flex items-center gap-4">
                @if ($foto)
                    <img src="{{ $foto->temporaryUrl() }}" alt="" class="border-line h-16 w-16 rounded-full border object-cover">
                @elseif (auth()->user()->foto_url)
                    <img src="{{ auth()->user()->foto_url }}" alt="" class="border-line h-16 w-16 rounded-full border object-cover">
                @else
                    <span class="bg-brand-red-ui flex h-16 w-16 items-center justify-center rounded-full text-xl font-bold text-white">
                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                    </span>
                @endif

                <div>
                    <label class="border-line hover:bg-cream-deep inline-block cursor-pointer rounded-lg border px-3 py-1.5 text-xs font-semibold transition-colors">
                        Cambiar foto
                        <input type="file" wire:model="foto" accept="image/*" class="hidden">
                    </label>
                    <p class="text-ink-soft mt-1 text-xs" wire:loading wire:target="foto">Subiendo…</p>
                    @error('foto') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <span class="text-ink-soft block text-sm font-semibold">Rol</span>
                <p class="text-ink mt-1.5 text-sm">{{ auth()->user()->rol_label }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-ink-soft block text-sm font-semibold" for="perfil-nombre">Nombre</label>
                    <input id="perfil-nombre" type="text" wire:model="nombre"
                        class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1.5 w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                    @error('nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-ink-soft block text-sm font-semibold" for="perfil-email">Correo ({{ '@'.\App\Models\User::DOMINIO_CORPORATIVO }})</label>
                    <input id="perfil-email" type="email" wire:model="email"
                        class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1.5 w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-ink-soft block text-sm font-semibold" for="perfil-email-google">Correo de Google (para iniciar sesión)</label>
                    <input id="perfil-email-google" type="email" wire:model="emailGoogle" placeholder="tu-correo@gmail.com"
                        class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1.5 w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                    @error('emailGoogle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="text-ink-soft block text-sm font-semibold" for="perfil-telefono">Teléfono</label>
                    <input id="perfil-telefono" type="text" wire:model="telefono"
                        class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1.5 w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                    @error('telefono') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="border-line border-t pt-4">
                <h3 class="text-ink-soft text-sm font-bold tracking-wide uppercase">Cambiar contraseña</h3>
                <p class="text-ink-soft mt-1 text-xs">Deja estos campos en blanco si no quieres cambiarla.</p>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="text-ink-soft block text-sm font-semibold" for="perfil-password-actual">Contraseña actual</label>
                        <input id="perfil-password-actual" type="password" wire:model="passwordActual" autocomplete="current-password"
                            class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1.5 w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                        @error('passwordActual') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-ink-soft block text-sm font-semibold" for="perfil-password">Nueva contraseña</label>
                        <input id="perfil-password" type="password" wire:model="password" autocomplete="new-password"
                            class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1.5 w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                        @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-ink-soft block text-sm font-semibold" for="perfil-password-confirm">Confirmar nueva contraseña</label>
                        <input id="perfil-password-confirm" type="password" wire:model="password_confirmation" autocomplete="new-password"
                            class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1.5 w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                    </div>
                </div>
            </div>

            <button type="submit"
                class="bg-brand-red-ui hover:bg-brand-red-dark self-start rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition-colors"
                wire:loading.attr="disabled" wire:target="guardar">
                <span wire:loading.remove wire:target="guardar">Guardar cambios</span>
                <span wire:loading wire:target="guardar">Guardando…</span>
            </button>
        </form>
    </div>
</div>
