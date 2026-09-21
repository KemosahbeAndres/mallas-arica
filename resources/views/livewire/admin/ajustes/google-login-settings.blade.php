@php
    $dominio = config('app.domain');
    $redirectUris = $dominio === 'localhost'
        ? [route('login.google.callback.local')]
        : [route('admin.login.google.callback')];
@endphp

<div class="flex flex-col gap-4">
    @if ($guardado)
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm font-medium text-green-800">
            {{ $guardado }}
        </div>
    @endif

    <div class="border-line rounded-lg border bg-white px-4 py-4">
        <h3 class="text-ink-soft text-sm font-bold tracking-wide uppercase">Estado</h3>

        @if ($config->configurado())
            <div class="mt-2 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-green-500"></span>
                <span class="text-ink text-sm font-medium">Configurado</span>
            </div>
            <dl class="mt-3 grid gap-1.5 text-sm">
                <div class="flex gap-2">
                    <dt class="text-ink-soft w-28 shrink-0">Client ID</dt>
                    <dd class="text-ink truncate font-mono text-xs">{{ $config->clientId() }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="text-ink-soft w-28 shrink-0">Client Secret</dt>
                    <dd class="text-ink font-mono text-xs">{{ $config->clientSecretParcial() }}</dd>
                </div>
            </dl>
        @else
            <div class="mt-2 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-gray-300"></span>
                <span class="text-ink-soft text-sm font-medium">Sin configurar</span>
            </div>
            <p class="text-ink-soft mt-2 text-sm">
                El botón "Iniciar sesión con Google" del login no funcionará hasta subir el archivo.
            </p>
        @endif
    </div>

    <div class="border-line rounded-lg border bg-white px-4 py-4">
        <h3 class="text-ink-soft text-sm font-bold tracking-wide uppercase">Redirect URI a autorizar en Google</h3>
        <p class="text-ink-soft mt-1 text-xs">
            En Google Cloud Console → Credentials → tu cliente OAuth → "Authorized redirect URIs", agrega:
        </p>
        <div class="mt-2 flex flex-col gap-1.5">
            @foreach ($redirectUris as $uri)
                <code class="border-line bg-cream-deep block rounded-lg border px-3 py-2 text-xs">{{ $uri }}</code>
            @endforeach
        </div>
    </div>

    <div class="border-line rounded-lg border bg-white px-4 py-4">
        <h3 class="text-ink-soft text-sm font-bold tracking-wide uppercase">
            {{ $config->configurado() ? 'Reemplazar archivo' : 'Subir archivo JSON de Google' }}
        </h3>
        <p class="text-ink-soft mt-1 text-xs">
            El archivo que descargas al crear el "OAuth client ID" en Google Cloud Console (tipo "Web application").
        </p>

        <form wire:submit="guardar" class="mt-3 flex flex-col gap-2">
            <input type="file" wire:model="archivo" accept="application/json,.json"
                class="border-line w-full rounded-lg border px-3 py-2 text-sm">
            @error('archivo') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

            <button type="submit"
                class="bg-brand-red-ui hover:bg-brand-red-dark self-start rounded-lg px-4 py-2 text-xs font-semibold text-white"
                wire:loading.attr="disabled" wire:target="guardar,archivo">
                <span wire:loading.remove wire:target="guardar">Guardar</span>
                <span wire:loading wire:target="guardar">Guardando…</span>
            </button>
        </form>

        @if ($config->configurado())
            <button type="button" wire:click="eliminar" wire:confirm="¿Quitar la configuración de Google? El botón de login dejará de funcionar."
                class="text-ink-soft mt-3 rounded-lg px-3 py-2 text-xs hover:bg-red-50 hover:text-red-600">
                Quitar configuración
            </button>
        @endif
    </div>
</div>
