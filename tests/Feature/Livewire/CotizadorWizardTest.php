<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Cotizador\CotizadorWizard;
use App\Models\Cotizacion;
use App\Models\Tarifa;
use App\Models\TipoEspacio;
use App\Models\TramoAltura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CotizadorWizardTest extends TestCase
{
    use RefreshDatabase;

    private TipoEspacio $ventana;

    private TramoAltura $tramoHasta1_5;

    private TramoAltura $tramoMas3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ventana = TipoEspacio::create([
            'slug' => 'ventana',
            'nombre' => 'Ventana',
            'permite_calculo' => true,
            'orden' => 1,
        ]);

        $this->tramoHasta1_5 = TramoAltura::create([
            'etiqueta' => 'Hasta 1,5 m',
            'altura_min' => 0,
            'altura_max' => 1.5,
            'requiere_visita' => false,
            'orden' => 1,
        ]);

        $this->tramoMas3 = TramoAltura::create([
            'etiqueta' => '+3 m',
            'altura_min' => 3,
            'altura_max' => null,
            'requiere_visita' => true,
            'orden' => 4,
        ]);

        Tarifa::create([
            'tipo_espacio_id' => $this->ventana->id,
            'tramo_altura_id' => $this->tramoHasta1_5->id,
            'precio_ml_min' => 8000,
            'precio_ml_max' => 9500,
            'vigente_desde' => now()->subDay()->toDateString(),
        ]);
    }

    public function test_el_lead_se_guarda_aunque_no_se_abra_whatsapp(): void
    {
        Livewire::test(CotizadorWizard::class)
            ->set('nombre', 'Juan Pérez')
            ->set('telefono', '+56912345678')
            ->set('direccion', 'Condominio Las Torres, depto 302')
            ->set('email', 'juan@example.com')
            ->set('items.0.tipo_espacio_id', $this->ventana->id)
            ->set('items.0.tramo_altura_id', $this->tramoHasta1_5->id)
            ->set('items.0.metros_lineales', 3.5)
            ->call('crearCotizacionYAbrirWhatsapp');

        $this->assertDatabaseCount('cotizaciones', 1);

        $cotizacion = Cotizacion::first();
        $this->assertSame('Juan Pérez', $cotizacion->nombre);
        $this->assertSame('borrador', $cotizacion->estado);
        $this->assertSame('Condominio Las Torres, depto 302', $cotizacion->direccion);
        $this->assertSame('juan@example.com', $cotizacion->email);
        $this->assertSame(1, $cotizacion->items()->count());
    }

    public function test_el_numero_generado_es_el_correlativo_del_id(): void
    {
        Livewire::test(CotizadorWizard::class)
            ->set('nombre', 'Juan Pérez')
            ->set('telefono', '+56912345678')
            ->set('items.0.tipo_espacio_id', $this->ventana->id)
            ->set('items.0.tramo_altura_id', $this->tramoHasta1_5->id)
            ->set('items.0.metros_lineales', 3.5)
            ->call('crearCotizacionYAbrirWhatsapp')
            ->assertSet('numeroGenerado', fn ($numero) => $numero === Cotizacion::first()->numero);

        $this->assertSame(str_pad((string) Cotizacion::first()->id, 4, '0', STR_PAD_LEFT), Cotizacion::first()->numero);
    }

    public function test_direccion_y_email_son_opcionales(): void
    {
        Livewire::test(CotizadorWizard::class)
            ->set('nombre', 'Juan Pérez')
            ->set('telefono', '+56912345678')
            ->set('items.0.tipo_espacio_id', $this->ventana->id)
            ->set('items.0.tramo_altura_id', $this->tramoHasta1_5->id)
            ->set('items.0.metros_lineales', 3.5)
            ->call('crearCotizacionYAbrirWhatsapp');

        $cotizacion = Cotizacion::first();
        $this->assertNull($cotizacion->direccion);
        $this->assertNull($cotizacion->email);
    }

    public function test_email_invalido_no_persiste(): void
    {
        Livewire::test(CotizadorWizard::class)
            ->set('nombre', 'Juan Pérez')
            ->set('telefono', '+56912345678')
            ->set('email', 'no-es-un-correo')
            ->set('items.0.tipo_espacio_id', $this->ventana->id)
            ->set('items.0.tramo_altura_id', $this->tramoHasta1_5->id)
            ->set('items.0.metros_lineales', 3.5)
            ->call('crearCotizacionYAbrirWhatsapp')
            ->assertHasErrors(['email']);

        $this->assertDatabaseCount('cotizaciones', 0);
    }

    public function test_descargar_pdf_persiste_la_cotizacion_y_entrega_el_archivo(): void
    {
        Livewire::test(CotizadorWizard::class)
            ->set('nombre', 'Juan Pérez')
            ->set('telefono', '+56912345678')
            ->set('items.0.tipo_espacio_id', $this->ventana->id)
            ->set('items.0.tramo_altura_id', $this->tramoHasta1_5->id)
            ->set('items.0.metros_lineales', 3.5)
            ->call('crearCotizacionYDescargarPdf')
            ->assertFileDownloaded();

        $this->assertDatabaseCount('cotizaciones', 1);
    }

    public function test_altura_mas_de_3m_persiste_como_lead_y_marca_requiere_visita(): void
    {
        Livewire::test(CotizadorWizard::class)
            ->set('nombre', 'Ana Soto')
            ->set('telefono', '+56987654321')
            ->set('items.0.tipo_espacio_id', $this->ventana->id)
            ->set('items.0.tramo_altura_id', $this->tramoMas3->id)
            ->set('items.0.metros_lineales', 5)
            ->call('crearCotizacionYAbrirWhatsapp');

        $cotizacion = Cotizacion::first();
        $this->assertTrue((bool) $cotizacion->requiere_visita);
        $this->assertSame(0, $cotizacion->total_min);
    }

    public function test_no_persiste_sin_nombre_ni_telefono(): void
    {
        Livewire::test(CotizadorWizard::class)
            ->set('items.0.tipo_espacio_id', $this->ventana->id)
            ->set('items.0.tramo_altura_id', $this->tramoHasta1_5->id)
            ->set('items.0.metros_lineales', 3.5)
            ->call('crearCotizacionYAbrirWhatsapp')
            ->assertHasErrors(['nombre', 'telefono']);

        $this->assertDatabaseCount('cotizaciones', 0);
    }

    public function test_honeypot_relleno_ignora_el_envio_en_silencio(): void
    {
        Livewire::test(CotizadorWizard::class)
            ->set('nombre', 'Bot Malicioso')
            ->set('telefono', '+56900000000')
            ->set('sitioWeb', 'https://spam.com')
            ->set('items.0.tipo_espacio_id', $this->ventana->id)
            ->set('items.0.tramo_altura_id', $this->tramoHasta1_5->id)
            ->set('items.0.metros_lineales', 3.5)
            ->call('crearCotizacionYAbrirWhatsapp');

        $this->assertDatabaseCount('cotizaciones', 0);
    }

    public function test_throttle_bloquea_tras_exceder_el_maximo_de_intentos(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Livewire::test(CotizadorWizard::class)
                ->set('nombre', 'Juan Pérez')
                ->set('telefono', '+56912345678')
                ->set('items.0.tipo_espacio_id', $this->ventana->id)
                ->set('items.0.tramo_altura_id', $this->tramoHasta1_5->id)
                ->set('items.0.metros_lineales', 3.5)
                ->call('crearCotizacionYAbrirWhatsapp');
        }

        $this->assertDatabaseCount('cotizaciones', 5);

        Livewire::test(CotizadorWizard::class)
            ->set('nombre', 'Juan Pérez')
            ->set('telefono', '+56912345678')
            ->set('items.0.tipo_espacio_id', $this->ventana->id)
            ->set('items.0.tramo_altura_id', $this->tramoHasta1_5->id)
            ->set('items.0.metros_lineales', 3.5)
            ->call('crearCotizacionYAbrirWhatsapp')
            ->assertHasErrors(['throttle']);

        $this->assertDatabaseCount('cotizaciones', 5);
    }

    public function test_agregar_y_quitar_items(): void
    {
        Livewire::test(CotizadorWizard::class)
            ->assertCount('items', 1)
            ->call('agregarItem')
            ->assertCount('items', 2)
            ->call('quitarItem', 0)
            ->assertCount('items', 1);
    }

    public function test_no_permite_quitar_el_ultimo_item(): void
    {
        Livewire::test(CotizadorWizard::class)
            ->call('quitarItem', 0)
            ->assertCount('items', 1);
    }
}
