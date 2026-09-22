<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Trabajos\TrabajoShow;
use App\Models\Cliente;
use App\Models\Trabajo;
use App\Models\TrabajoFoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class TrabajoShowTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    private function crearOt(array $atributos = []): Trabajo
    {
        $cliente = Cliente::create(['nombre' => 'Cliente '.uniqid()]);

        return Trabajo::create(array_merge([
            'cliente_id' => $cliente->id,
            'titulo' => 'Instalación',
            'estado' => 'pendiente',
        ], $atributos));
    }

    public function test_colaborador_asignado_puede_ver_su_ot(): void
    {
        $colaborador = $this->actuarComoUsuario('colaborador');
        $ot = $this->crearOt();
        $ot->colaboradores()->attach($colaborador);

        Livewire::test(TrabajoShow::class, ['trabajo' => $ot])->assertOk();
    }

    public function test_colaborador_no_asignado_recibe_403(): void
    {
        $this->actuarComoUsuario('colaborador');
        $ot = $this->crearOt();

        Livewire::test(TrabajoShow::class, ['trabajo' => $ot])->assertForbidden();
    }

    public function test_supervisor_puede_ver_cualquier_ot(): void
    {
        $this->actuarComoUsuario('supervisor');
        $ot = $this->crearOt();

        Livewire::test(TrabajoShow::class, ['trabajo' => $ot])->assertOk();
    }

    public function test_colaborador_puede_subir_fotos(): void
    {
        Storage::fake('public');
        $colaborador = $this->actuarComoUsuario('colaborador');
        $ot = $this->crearOt();
        $ot->colaboradores()->attach($colaborador);

        Livewire::test(TrabajoShow::class, ['trabajo' => $ot])
            ->set('fotosNuevas', [UploadedFile::fake()->image('foto1.jpg')])
            ->call('subirFotos')
            ->assertHasNoErrors();

        $this->assertSame(1, $ot->fotos()->count());
        Storage::disk('public')->assertExists($ot->fotos()->first()->foto_path);
    }

    public function test_subir_foto_no_imagen_falla_validacion(): void
    {
        Storage::fake('public');
        $this->actuarComoAdmin();
        $ot = $this->crearOt();

        Livewire::test(TrabajoShow::class, ['trabajo' => $ot])
            ->set('fotosNuevas', [UploadedFile::fake()->create('doc.pdf', 100)])
            ->call('subirFotos')
            ->assertHasErrors('fotosNuevas.*');
    }

    public function test_colaborador_no_puede_marcar_pendiente_ni_cancelada(): void
    {
        $colaborador = $this->actuarComoUsuario('colaborador');
        $ot = $this->crearOt();
        $ot->colaboradores()->attach($colaborador);

        Livewire::test(TrabajoShow::class, ['trabajo' => $ot])
            ->call('cambiarEstado', 'cancelada')
            ->assertHasErrors('estado');

        $this->assertSame('pendiente', $ot->refresh()->estado);
    }

    public function test_marcar_ejecutada_sin_minimo_de_fotos_muestra_error(): void
    {
        $this->actuarComoAdmin();
        $ot = $this->crearOt(['cantidad_ventanas' => 2]);

        Livewire::test(TrabajoShow::class, ['trabajo' => $ot])
            ->call('cambiarEstado', 'ejecutada')
            ->assertHasErrors('estado');

        $this->assertSame('pendiente', $ot->refresh()->estado);
    }

    public function test_marcar_ejecutada_con_minimo_de_fotos_cumplido_funciona(): void
    {
        $this->actuarComoAdmin();
        $ot = $this->crearOt(['cantidad_ventanas' => 1]);
        TrabajoFoto::factory()->create(['trabajo_id' => $ot->id]);

        Livewire::test(TrabajoShow::class, ['trabajo' => $ot])
            ->call('cambiarEstado', 'ejecutada')
            ->assertHasNoErrors();

        $this->assertSame('ejecutada', $ot->refresh()->estado);
    }

    public function test_colaborador_no_puede_eliminar_fotos(): void
    {
        $colaborador = $this->actuarComoUsuario('colaborador');
        $ot = $this->crearOt();
        $ot->colaboradores()->attach($colaborador);
        $foto = TrabajoFoto::factory()->create(['trabajo_id' => $ot->id]);

        Livewire::test(TrabajoShow::class, ['trabajo' => $ot])
            ->call('eliminarFoto', $foto->id)
            ->assertForbidden();
    }

    public function test_supervisor_no_puede_eliminar_fotos(): void
    {
        $this->actuarComoUsuario('supervisor');
        $ot = $this->crearOt();
        $foto = TrabajoFoto::factory()->create(['trabajo_id' => $ot->id]);

        Livewire::test(TrabajoShow::class, ['trabajo' => $ot])
            ->call('eliminarFoto', $foto->id)
            ->assertForbidden();
    }
}
