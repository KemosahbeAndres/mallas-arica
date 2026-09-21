<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Perfil\PerfilForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class PerfilFormTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    public function test_cualquier_rol_puede_editar_su_propio_perfil(): void
    {
        $usuario = $this->actuarComoUsuario('colaborador');

        Livewire::test(PerfilForm::class)
            ->set('nombre', 'Nombre Actualizado')
            ->set('telefono', '+56 9 8888 7777')
            ->call('guardar')
            ->assertHasNoErrors();

        $usuario->refresh();
        $this->assertSame('Nombre Actualizado', $usuario->name);
        $this->assertSame('+56 9 8888 7777', $usuario->telefono);
    }

    public function test_no_permite_dejar_el_nombre_o_correo_vacios(): void
    {
        $this->actuarComoUsuario('supervisor');

        Livewire::test(PerfilForm::class)
            ->set('nombre', '')
            ->set('email', '')
            ->call('guardar')
            ->assertHasErrors(['nombre', 'email']);
    }

    public function test_correo_no_puede_repetir_el_de_otro_usuario(): void
    {
        $this->actuarComoUsuario('colaborador');
        User::factory()->rol('colaborador')->create(['email' => 'ocupado@mallasarica.cl']);

        Livewire::test(PerfilForm::class)
            ->set('email', 'ocupado@mallasarica.cl')
            ->call('guardar')
            ->assertHasErrors('email');
    }

    public function test_cambiar_password_exige_la_actual_correcta(): void
    {
        $usuario = $this->actuarComoUsuario('colaborador');

        Livewire::test(PerfilForm::class)
            ->set('passwordActual', 'incorrecta')
            ->set('password', 'nuevaClave123')
            ->set('password_confirmation', 'nuevaClave123')
            ->call('guardar')
            ->assertHasErrors('passwordActual');

        $this->assertTrue(Hash::check('password', $usuario->refresh()->password));
    }

    public function test_cambiar_password_con_la_actual_correcta_funciona(): void
    {
        $usuario = $this->actuarComoUsuario('colaborador');

        Livewire::test(PerfilForm::class)
            ->set('passwordActual', 'password')
            ->set('password', 'nuevaClave123')
            ->set('password_confirmation', 'nuevaClave123')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('nuevaClave123', $usuario->refresh()->password));
    }

    public function test_no_puede_cambiar_su_propio_rol(): void
    {
        $usuario = $this->actuarComoUsuario('colaborador');

        Livewire::test(PerfilForm::class)
            ->set('nombre', 'Sigo Colaborador')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame('colaborador', $usuario->refresh()->rol);
    }

    public function test_sube_su_propia_foto(): void
    {
        Storage::fake('public');
        $usuario = $this->actuarComoUsuario('colaborador');

        Livewire::test(PerfilForm::class)
            ->set('foto', UploadedFile::fake()->image('yo.jpg'))
            ->call('guardar')
            ->assertHasNoErrors();

        $usuario->refresh();
        $this->assertNotNull($usuario->foto_path);
        Storage::disk('public')->assertExists($usuario->foto_path);
    }

    public function test_invitado_no_accede_a_su_perfil(): void
    {
        $this->getAdmin('/perfil')->assertRedirect(route('admin.login'));
    }
}
