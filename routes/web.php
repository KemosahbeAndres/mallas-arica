<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');

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
