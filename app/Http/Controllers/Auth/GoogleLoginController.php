<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GoogleOAuthConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * Login con Google del panel admin. Credenciales gestionadas por el
 * super_admin en Ajustes › Google SSO (tabla `configuraciones`), no en
 * .env — ver GoogleOAuthConfig. No auto-registra: solo deja entrar a un
 * usuario que ya existe en /admin/usuarios y tiene un `email_google`
 * vinculado que coincide con la cuenta (el email principal siempre es del
 * dominio corporativo, distinto del correo personal usado para el SSO).
 *
 * Junto con el login se pide siempre el scope de Google Calendar
 * (calendar.events) para poder sincronizar la agenda en background — ver
 * GoogleCalendarService. `access_type=offline` se pide siempre (no muestra
 * pantalla extra); `prompt=consent` (que sí la muestra) se omite por
 * defecto para que el login sea silencioso cuando el usuario ya autorizó
 * el scope antes. Si Google no devuelve refresh_token (porque es la
 * primera vez, o porque el usuario revocó el acceso) y no había uno
 * guardado, callback() reintenta una única vez forzando `prompt=consent`
 * (guard en sesión — SESSION_REINTENTO_CONSENT — evita un loop infinito si
 * Google nunca llega a entregar el refresh_token).
 *
 * Solo en local (config('app.domain') === 'localhost'): Google no acepta
 * "admin.localhost" ni como origen ni como redirect URI de OAuth, así que
 * TODO el intercambio (el salto inicial `redirect()` Y el `callback()`)
 * ocurre en "localhost" sin subdominio (ver routes/web.php: rutas
 * `login.google.local` / `login.google.callback.local`, y el botón de
 * /login que enlaza ahí en vez de a admin.login.google). Al terminar, se
 * reenvía a "admin.localhost" con un token de un solo uso — la cookie de
 * sesión de Laravel es por host exacto, así que no se puede autenticar
 * directo en el dominio equivocado. En dev/staging/prod el dominio real ya
 * sirve como origen y redirect URI, así que el flujo es directo en
 * admin.{dominio}, sin este paso extra.
 */
class GoogleLoginController extends Controller
{
    private const TOKEN_TTL_SEGUNDOS = 60;

    private const SCOPE_CALENDAR = 'https://www.googleapis.com/auth/calendar.events';

    /** Clave de sesión que marca que ya se intentó forzar el consentimiento en este flujo. */
    private const SESSION_REINTENTO_CONSENT = 'google-login-reintento-consent';

    public function redirect(GoogleOAuthConfig $config): RedirectResponse
    {
        $this->configurarDriver($config);

        return $this->driverConScope()
            ->with(['access_type' => 'offline'])
            ->redirect();
    }

    public function callback(GoogleOAuthConfig $config): RedirectResponse
    {
        $this->configurarDriver($config);

        $cuentaGoogle = $this->driverConScope()->user();

        $usuario = User::where('email_google', $cuentaGoogle->getEmail())->first();

        if (! $usuario) {
            return redirect()->route('admin.login')->withErrors([
                'email' => 'Esa cuenta de Google no está vinculada a ningún usuario del panel. Pide que la agreguen en tu perfil.',
            ]);
        }

        if (! $usuario->google_id) {
            $usuario->update(['google_id' => $cuentaGoogle->getId()]);
        }

        $sinRefreshToken = ! $cuentaGoogle->refreshToken && ! $usuario->google_refresh_token;

        if ($sinRefreshToken && ! request()->session()->pull(self::SESSION_REINTENTO_CONSENT)) {
            request()->session()->put(self::SESSION_REINTENTO_CONSENT, true);

            return $this->driverConScope()
                ->with(['access_type' => 'offline', 'prompt' => 'consent'])
                ->redirect();
        }

        $usuario->update([
            'google_token' => $cuentaGoogle->token,
            'google_refresh_token' => $cuentaGoogle->refreshToken ?? $usuario->google_refresh_token,
            'google_token_expires_at' => $cuentaGoogle->expiresIn ? now()->addSeconds($cuentaGoogle->expiresIn) : null,
        ]);

        if (config('app.domain') === 'localhost') {
            $token = Str::random(40);
            Cache::put("google-login-token:{$token}", $usuario->id, self::TOKEN_TTL_SEGUNDOS);

            return redirect()->to(route('admin.login.google.consumir', ['token' => $token]));
        }

        Auth::login($usuario, remember: true);
        request()->session()->regenerate();

        return redirect()->route('admin.resumen');
    }

    /** Solo local: intercambia el token de un solo uso por una sesión ya en admin.localhost. */
    public function consumirToken(string $token): RedirectResponse
    {
        $userId = Cache::pull("google-login-token:{$token}");

        if (! $userId || ! ($usuario = User::find($userId))) {
            return redirect()->route('admin.login')->withErrors([
                'email' => 'El enlace de Google expiró o ya se usó. Intenta de nuevo.',
            ]);
        }

        Auth::login($usuario, remember: true);
        request()->session()->regenerate();

        return redirect()->route('admin.resumen');
    }

    private function callbackUrl(): string
    {
        return config('app.domain') === 'localhost'
            ? route('login.google.callback.local')
            : route('admin.login.google.callback');
    }

    private function configurarDriver(GoogleOAuthConfig $config): void
    {
        abort_unless($config->configurado(), 503, 'El login con Google no está configurado todavía.');

        config(['services.google' => [
            'client_id' => $config->clientId(),
            'client_secret' => $config->clientSecret(),
            'redirect' => $this->callbackUrl(),
        ]]);
    }

    private function driverConScope()
    {
        return Socialite::driver('google')->scopes([self::SCOPE_CALENDAR]);
    }
}
