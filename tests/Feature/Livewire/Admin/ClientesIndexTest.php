<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Clientes\ClientesIndex;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class ClientesIndexTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComoAdmin();
    }

    public function test_crea_un_cliente_con_direcciones(): void
    {
        Livewire::test(ClientesIndex::class)
            ->call('nuevo')
            ->set('nombre', 'María González')
            ->set('telefono', '+56 9 5521 0032')
            ->set('email', 'maria@gmail.com')
            ->call('agregarDireccion')
            ->set('direcciones.0.direccion', 'Los Aromos 221, Arica')
            ->set('direcciones.0.etiqueta', 'Casa')
            ->call('guardar')
            ->assertHasNoErrors();

        $cliente = Cliente::with('direcciones')->firstWhere('nombre', 'María González');
        $this->assertNotNull($cliente);
        $this->assertSame('+56 9 5521 0032', $cliente->telefono);
        $this->assertCount(1, $cliente->direcciones);
        $this->assertSame('Los Aromos 221, Arica', $cliente->direcciones->first()->direccion);
    }

    public function test_nombre_es_obligatorio(): void
    {
        Livewire::test(ClientesIndex::class)
            ->call('nuevo')
            ->set('nombre', '')
            ->call('guardar')
            ->assertHasErrors('nombre');

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_direccion_vacia_no_guarda(): void
    {
        Livewire::test(ClientesIndex::class)
            ->call('nuevo')
            ->set('nombre', 'Sin dirección')
            ->call('agregarDireccion')
            ->set('direcciones.0.direccion', '')
            ->call('guardar')
            ->assertHasErrors('direcciones.0.direccion');
    }

    public function test_editar_actualiza_y_sincroniza_direcciones(): void
    {
        $cliente = Cliente::create(['nombre' => 'Pedro', 'telefono' => '111']);
        $d1 = $cliente->direcciones()->create(['direccion' => 'Vieja 1']);
        $cliente->direcciones()->create(['direccion' => 'Vieja 2']);

        Livewire::test(ClientesIndex::class)
            ->call('seleccionar', $cliente->id)
            ->set('nombre', 'Pedro Rojas')
            ->set('direcciones.0.direccion', 'Nueva 1')
            ->call('quitarDireccion', 1)
            ->call('guardar')
            ->assertHasNoErrors();

        $cliente->refresh()->load('direcciones');
        $this->assertSame('Pedro Rojas', $cliente->nombre);
        $this->assertCount(1, $cliente->direcciones);
        $this->assertSame('Nueva 1', $cliente->direcciones->first()->direccion);
        $this->assertSame($d1->id, $cliente->direcciones->first()->id);
    }

    public function test_eliminar_hace_soft_delete_del_cliente(): void
    {
        $cliente = Cliente::create(['nombre' => 'Temporal']);

        Livewire::test(ClientesIndex::class)
            ->call('seleccionar', $cliente->id)
            ->call('eliminar');

        $this->assertSoftDeleted('clientes', ['id' => $cliente->id]);
    }

    public function test_busqueda_filtra_por_nombre_telefono_o_correo(): void
    {
        Cliente::create(['nombre' => 'Ana Torres', 'telefono' => '555-111']);
        Cliente::create(['nombre' => 'Beto Silva', 'telefono' => '555-222', 'email' => 'beto@x.cl']);

        Livewire::test(ClientesIndex::class)
            ->set('buscar', 'Torres')
            ->assertSee('Ana Torres')
            ->assertDontSee('Beto Silva')
            ->set('buscar', 'beto@x.cl')
            ->assertSee('Beto Silva')
            ->assertDontSee('Ana Torres');
    }

    public function test_parametro_seleccionado_invalido_no_rompe_la_pagina(): void
    {
        Livewire::withUrlParams(['seleccionado' => 999])
            ->test(ClientesIndex::class)
            ->assertOk()
            ->assertSet('seleccionado', null);
    }
}
