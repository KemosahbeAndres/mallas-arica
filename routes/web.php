<?php

use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Galeria\GaleriaIndex;
use App\Livewire\Admin\Leads\LeadDetalle;
use App\Livewire\Admin\Leads\LeadsIndex;
use App\Livewire\Admin\Proximamente;
use App\Livewire\Admin\SitioWeb\SitioWebPanel;
use App\Livewire\Admin\Tarifas\TarifasMatriz;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->get('/login', Login::class)->name('login');

    Route::middleware('auth')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.sitio-web'));

        // CRM «Sitio web» (Sprint 8): contenido, imágenes y FAQ editables.
        Route::get('/sitio-web', SitioWebPanel::class)->name('sitio-web');

        // Chrome del CRM completo (diseño/dashboard-v1.pdf). Las secciones aún
        // no construidas apuntan a Proximamente hasta su sprint (9+).
        Route::get('/resumen', Proximamente::class)->name('resumen')
            ->defaults('seccion', 'Resumen')
            ->defaults('detalle', 'El dashboard con la actividad de la semana y las últimas cotizaciones llega en una próxima entrega.');
        Route::get('/cotizaciones', Proximamente::class)->name('cotizaciones')
            ->defaults('seccion', 'Cotizaciones')
            ->defaults('detalle', 'El rediseño de cotizaciones con folio y estados es la última pieza del CRM. Por ahora los leads se ven en la sección Tarifas → Leads del panel anterior.');
        Route::get('/clientes', Proximamente::class)->name('clientes')
            ->defaults('seccion', 'Clientes')
            ->defaults('detalle', 'El historial de instalaciones por cliente y las alertas de mantención llegan en una próxima entrega.');
        Route::get('/calendario', Proximamente::class)->name('calendario')
            ->defaults('seccion', 'Calendario')
            ->defaults('detalle', 'La agenda de trabajos y la sincronización con Google Calendar llegan en una próxima entrega.');

        // Rutas del panel del Sprint 5 — siguen vivas (enlaces guardados, tests).
        Route::get('/tarifas', TarifasMatriz::class)->name('tarifas');
        Route::get('/leads', LeadsIndex::class)->name('leads.index');
        Route::get('/leads/{cotizacion}', LeadDetalle::class)->name('leads.show');
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
