<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Leads\LeadsIndex;
use App\Models\Cotizacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class LeadsIndexTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actuarComoAdmin();
    }

    public function test_lista_las_cotizaciones(): void
    {
        Cotizacion::create(['nombre' => 'Juan', 'telefono' => '111', 'canal' => 'web', 'estado' => 'borrador']);
        Cotizacion::create(['nombre' => 'Ana', 'telefono' => '222', 'canal' => 'web', 'estado' => 'contactado']);

        Livewire::test(LeadsIndex::class)
            ->assertSee('Juan')
            ->assertSee('Ana');
    }

    public function test_filtro_por_estado_solo_muestra_coincidencias(): void
    {
        Cotizacion::create(['nombre' => 'Juan', 'telefono' => '111', 'canal' => 'web', 'estado' => 'borrador']);
        Cotizacion::create(['nombre' => 'Ana', 'telefono' => '222', 'canal' => 'web', 'estado' => 'contactado']);

        Livewire::test(LeadsIndex::class)
            ->set('estado', 'contactado')
            ->assertSee('Ana')
            ->assertDontSee('Juan');
    }
}
