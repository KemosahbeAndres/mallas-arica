<?php

use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\CotizacionPdfController;
use App\Livewire\Admin\Agenda\AgendaIndex;
use App\Livewire\Admin\Agenda\MiAgenda;
use App\Livewire\Admin\Ajustes\AjustesPanel;
use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Clientes\ClientesIndex;
use App\Livewire\Admin\Cotizaciones\CotizacionesIndex;
use App\Livewire\Admin\Cotizaciones\CotizacionForm;
use App\Livewire\Admin\Resumen\ResumenIndex;
use App\Livewire\Admin\SitioWeb\SitioWebPanel;
use App\Livewire\Admin\Trabajos\TrabajoShow;
use App\Livewire\Admin\Usuarios\UsuariosIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

$dominio = config('app.domain');

// Panel admin, aislado en su propio subdominio (admin.{APP_DOMAIN}). Los
// nombres de ruta (admin.*) no cambian, solo el host que los sirve.
Route::domain('admin.'.$dominio)->name('admin.')->group(function () {
    Route::middleware('guest')->get('/login', Login::class)->name('login');
    Route::middleware('guest')->get('/login/google', [GoogleLoginController::class, 'redirect'])->name('login.google');
    Route::middleware('guest')->get('/login/google/callback', [GoogleLoginController::class, 'callback'])->name('login.google.callback');
    Route::middleware('guest')->get('/login/google/consumir/{token}', [GoogleLoginController::class, 'consumirToken'])->name('login.google.consumir');

    Route::middleware('auth')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.resumen'));

        // Dashboard «Resumen» (Sprint 11): KPIs + agenda de la semana + últimas cotizaciones.
        Route::get('/resumen', ResumenIndex::class)->name('resumen');

        // CRM «Cotizaciones» (Sprint 12): cotizaciones internas con ítems libres,
        // estados borrador→generada→aceptada→rechazada, PDF y creación de OT al aceptar.
        Route::get('/cotizaciones', CotizacionesIndex::class)->name('cotizaciones');
        Route::get('/cotizaciones/nueva', CotizacionForm::class)->name('cotizaciones.nueva');
        Route::get('/cotizaciones/{cotizacion}/editar', CotizacionForm::class)->name('cotizaciones.editar');
        Route::get('/cotizaciones/{cotizacion}/pdf', [CotizacionPdfController::class, 'descargar'])
            ->name('cotizaciones.pdf');

        Route::get('/clientes', ClientesIndex::class)->name('clientes');

        // «Agenda» (fusión de Calendario + Trabajos): calendario mensual + OT
        // pendientes de agendar + agenda del mes, en una sola página.
        Route::get('/agenda', AgendaIndex::class)->name('agenda');

        // Agenda de solo lectura para colaborador: sus propias OT asignadas.
        Route::get('/mi-agenda', MiAgenda::class)->name('agenda.mia');

        // Ficha de una OT: estado + evidencia fotográfica. Usada por
        // colaborador (su propia OT), supervisor y administrador/super_admin.
        Route::get('/trabajos/{trabajo}', TrabajoShow::class)->name('trabajos.show');

        // CRM «Sitio web» (Sprint 8): contenido, imágenes y FAQ editables.
        Route::get('/sitio-web', SitioWebPanel::class)->name('sitio-web');
        Route::redirect('/galeria', '/sitio-web?tab=imagenes')->name('galeria');

        // Gestión de usuarios del panel (Super Administrador y Administrador).
        Route::get('/usuarios', UsuariosIndex::class)->name('usuarios');

        // «Ajustes»: página única con submenú vertical (Perfil, Apariencia,
        // Google SSO — este último solo super_admin), accesible desde el
        // dropdown de usuario del navbar, no desde la navbar horizontal.
        Route::get('/ajustes', AjustesPanel::class)->name('ajustes');
        Route::redirect('/perfil', '/ajustes')->name('perfil');

        Route::post('/logout', function () {
            Auth::guard('web')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('admin.login');
        })->name('logout');
    });
});

// Sitio público: dominio canónico y www (Traefik ya redirige www → canónico
// en prod; aquí cubrimos también el caso servido directo, p. ej. en dev).
Route::domain($dominio)->group(function () use ($dominio) {
    Route::view('/', 'landing')->name('home');

    // Google no acepta "admin.localhost" como redirect URI de OAuth (solo
    // "localhost" pelado o un dominio HTTPS real) — en local únicamente,
    // este endpoint sin subdominio delega al mismo controlador que
    // admin.login.google.callback. No existe en dev/prod: ahí el dominio
    // real (mallas.tinorte.cl / mallasarica.cl) sí sirve como redirect URI.
    if ($dominio === 'localhost') {
        Route::get('/login/google/callback', [GoogleLoginController::class, 'callback'])
            ->name('login.google.callback.local');
    }

    Route::get('/sitemap.xml', function () {
        return response()
            ->view('sitemap')
            ->header('Content-Type', 'application/xml');
    })->name('sitemap')->middleware('cache.headers:public;max_age=3600');

    Route::get('/robots.txt', function () {
        return response(
            "User-agent: *\nDisallow:\n\nSitemap: ".url('/sitemap.xml')."\n"
        )->header('Content-Type', 'text/plain');
    })->name('robots')->middleware('cache.headers:public;max_age=3600');

    // Mapa de 301 desde el sitio Wix anterior. No se tuvo acceso al listado real
    // de URLs de producción (ver CLAUDE.md §7 Migración desde Wix) — cubre las
    // rutas típicas de un sitio Wix de landing (incluye /page4, reportada como
    // rota en la navegación) redirigidas a la sección equivalente de la nueva
    // página única. Ajustar si aparecen más rutas al revisar los logs de Wix.
    Route::redirect('/page4', '/#nosotros', 301);
    Route::redirect('/servicios', '/#servicios', 301);
    Route::redirect('/cotizar', '/#cotizador', 301);
    Route::redirect('/cotizacion', '/#cotizador', 301);
    Route::redirect('/galeria', '/#galeria', 301);
    Route::redirect('/nosotros', '/#nosotros', 301);
    Route::redirect('/contacto', '/#nosotros', 301);
    Route::redirect('/preguntas-frecuentes', '/#faq', 301);
    Route::redirect('/faq', '/#faq', 301);
    Route::redirect('/home', '/', 301);
    Route::redirect('/index', '/', 301);
});

// www.{APP_DOMAIN} → dominio canónico (sin www), cualquier ruta. En prod
// Traefik ya hace este 301 a nivel de proxy (ver deploy/docker-compose.yml);
// esto cubre el caso de servir la app directo sin ese middleware (dev/staging).
Route::domain('www.'.$dominio)->group(function () use ($dominio) {
    $aCanonico = fn () => redirect()->to('https://'.$dominio.request()->getRequestUri(), 301);

    Route::any('/', $aCanonico);
    Route::any('{cualquiera}', $aCanonico)->where('cualquiera', '.*');
});
