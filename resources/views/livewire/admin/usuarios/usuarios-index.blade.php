<div class="grid gap-4 lg:grid-cols-[20rem_1fr]">
    {{-- Lista maestra --}}
    <div class="flex flex-col gap-3">
        <button
            type="button"
            wire:click="nuevo"
            class="bg-brand-red-ui hover:bg-brand-red-dark self-start rounded-lg px-4 py-2 text-sm font-semibold text-white transition-colors"
        >
            + Nuevo usuario
        </button>

        <input
            type="search"
            wire:model.live.debounce.300ms="buscar"
            placeholder="Buscar por nombre o correo…"
            class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 w-full rounded-lg border px-3 py-2 text-sm focus:ring"
        >

        <div class="border-line divide-line divide-y overflow-hidden rounded-lg border bg-white">
            @forelse ($this->usuarios as $usuario)
                <button
                    type="button"
                    wire:key="usuario-{{ $usuario->id }}"
                    wire:click="seleccionar({{ $usuario->id }})"
                    class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition-colors {{ $seleccionado === $usuario->id ? 'bg-cream-deep' : 'hover:bg-cream-deep/60' }}"
                >
                    @if ($usuario->foto_url)
                        <img src="{{ $usuario->foto_url }}" alt="" class="h-8 w-8 shrink-0 rounded-full object-cover">
                    @else
                        <span class="bg-brand-red-ui flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white">
                            {{ mb_substr($usuario->name, 0, 1) }}
                        </span>
                    @endif
                    <span class="min-w-0">
                        <span class="text-ink block truncate text-sm font-semibold">{{ $usuario->name }}</span>
                        <span class="text-ink-soft block truncate text-xs">{{ $usuario->rol_label }}</span>
                    </span>
                </button>
            @empty
                <p class="text-ink-soft px-4 py-6 text-center text-sm">
                    {{ $buscar !== '' ? 'Sin resultados.' : 'Todavía no hay usuarios.' }}
                </p>
            @endforelse
        </div>
    </div>

    {{-- Detalle / formulario --}}
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
                @elseif (optional($this->usuarioSeleccionado)->foto_url)
                    <img src="{{ $this->usuarioSeleccionado->foto_url }}" alt="" class="border-line h-16 w-16 rounded-full border object-cover">
                @else
                    <span class="bg-brand-red-ui flex h-16 w-16 items-center justify-center rounded-full text-xl font-bold text-white">
                        {{ $nombre !== '' ? mb_substr($nombre, 0, 1) : '?' }}
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

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-ink-soft block text-sm font-semibold" for="usr-nombre">Nombre</label>
                    <input id="usr-nombre" type="text" wire:model="nombre"
                        class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1.5 w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                    @error('nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-ink-soft block text-sm font-semibold" for="usr-email">Correo</label>
                    <input id="usr-email" type="email" wire:model="email"
                        class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1.5 w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-ink-soft block text-sm font-semibold" for="usr-telefono">Teléfono</label>
                    <input id="usr-telefono" type="text" wire:model="telefono"
                        class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1.5 w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                    @error('telefono') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-ink-soft block text-sm font-semibold" for="usr-rol">Rol</label>
                    <select id="usr-rol" wire:model="rol"
                        {{ optional($this->usuarioSeleccionado)->esSuperAdmin() ? 'disabled' : '' }}
                        class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1.5 w-full rounded-lg border px-3 py-2 text-sm focus:ring disabled:bg-cream-deep disabled:text-ink-soft">
                        @foreach (\App\Models\User::ROLES as $valor)
                            @if ($valor !== \App\Models\User::ROL_SUPER_ADMIN || $rol === \App\Models\User::ROL_SUPER_ADMIN)
                                <option value="{{ $valor }}">{{ \App\Models\User::ROLES_LABELS[$valor] }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('rol') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="border-line grid gap-4 border-t pt-4 sm:grid-cols-2">
                <div>
                    <label class="text-ink-soft block text-sm font-semibold" for="usr-password">
                        {{ $seleccionado ? 'Nueva contraseña' : 'Contraseña' }}
                    </label>
                    <input id="usr-password" type="password" wire:model="password" autocomplete="new-password"
                        placeholder="{{ $seleccionado ? 'Dejar en blanco para no cambiarla' : '' }}"
                        class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1.5 w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-ink-soft block text-sm font-semibold" for="usr-password-confirm">Confirmar contraseña</label>
                    <input id="usr-password-confirm" type="password" wire:model="password_confirmation" autocomplete="new-password"
                        class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1.5 w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                </div>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit"
                    class="bg-brand-red-ui hover:bg-brand-red-dark rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition-colors"
                    wire:loading.attr="disabled" wire:target="guardar">
                    <span wire:loading.remove wire:target="guardar">Guardar usuario</span>
                    <span wire:loading wire:target="guardar">Guardando…</span>
                </button>

                @if ($seleccionado && $seleccionado !== auth()->id() && ! optional($this->usuarioSeleccionado)->esSuperAdmin())
                    <button type="button" wire:click="eliminar" wire:confirm="¿Eliminar este usuario? Perderá el acceso al panel."
                        class="text-ink-soft rounded-lg px-3 py-2 text-sm hover:bg-red-50 hover:text-red-600">
                        Eliminar usuario
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>
