<?php

use App\Http\Controllers\CotizacionPdfController;
use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Calendario\CalendarioIndex;
use App\Livewire\Admin\Clientes\ClientesIndex;
use App\Livewire\Admin\Cotizaciones\CotizacionesIndex;
use App\Livewire\Admin\Cotizaciones\CotizacionForm;
use App\Livewire\Admin\Galeria\GaleriaIndex;
use App\Livewire\Admin\Resumen\ResumenIndex;
use App\Livewire\Admin\SitioWeb\SitioWebPanel;
use App\Livewire\Admin\Trabajos\TrabajosIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->get('/login', Login::class)->name('login');

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
        Route::get('/calendario', CalendarioIndex::class)->name('calendario');

        // Vista «Trabajos»: OT ordenadas por fecha (hoy / resto de la semana / realizadas).
        Route::get('/trabajos', TrabajosIndex::class)->name('trabajos');

        // CRM «Sitio web» (Sprint 8): contenido, imágenes y FAQ editables.
        Route::get('/sitio-web', SitioWebPanel::class)->name('sitio-web');
        Route::get('/galeria', GaleriaIndex::class)->name('galeria');

        Route::post('/logout', function () {
            Auth::guard('web')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('admin.login');
        })->name('logout');
    });
});

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
