<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Usuarios\UsuariosIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class UsuariosIndexTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    public function test_super_admin_crea_un_usuario(): void
    {
        $this->actuarComoAdmin();

        Livewire::test(UsuariosIndex::class)
            ->call('nuevo')
            ->set('nombre', 'Juan Pérez')
            ->set('email', 'juan@mallasarica.cl')
            ->set('telefono', '+56 9 1111 2222')
            ->set('rol', 'colaborador')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('guardar')
            ->assertHasNoErrors();

        $usuario = User::firstWhere('email', 'juan@mallasarica.cl');
        $this->assertNotNull($usuario);
        $this->assertSame('colaborador', $usuario->rol);
        $this->assertTrue(Hash::check('password123', $usuario->password));
    }

    public function test_administrador_tambien_puede_gestionar_usuarios(): void
    {
        $this->actuarComoUsuario('administrador');

        Livewire::test(UsuariosIndex::class)
            ->call('nuevo')
            ->set('nombre', 'Ana Soto')
            ->set('email', 'ana@mallasarica.cl')
            ->set('rol', 'supervisor')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'ana@mallasarica.cl', 'rol' => 'supervisor']);
    }

    public function test_supervisor_no_puede_acceder_al_crud_de_usuarios(): void
    {
        $this->actuarComoUsuario('supervisor');

        Livewire::test(UsuariosIndex::class)->assertForbidden();
    }

    public function test_colaborador_no_puede_acceder_al_crud_de_usuarios(): void
    {
        $this->actuarComoUsuario('colaborador');

        Livewire::test(UsuariosIndex::class)->assertForbidden();
    }

    public function test_nombre_email_y_password_son_obligatorios_al_crear(): void
    {
        $this->actuarComoAdmin();

        Livewire::test(UsuariosIndex::class)
            ->call('nuevo')
            ->set('nombre', '')
            ->set('email', '')
            ->set('password', '')
            ->call('guardar')
            ->assertHasErrors(['nombre', 'email', 'password']);
    }

    public function test_email_duplicado_no_guarda(): void
    {
        $this->actuarComoAdmin();
        User::factory()->rol('colaborador')->create(['email' => 'existente@mallasarica.cl']);

        Livewire::test(UsuariosIndex::class)
            ->call('nuevo')
            ->set('nombre', 'Otro')
            ->set('email', 'existente@mallasarica.cl')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('guardar')
            ->assertHasErrors('email');
    }

    public function test_editar_no_exige_contrasena(): void
    {
        $admin = $this->actuarComoAdmin();
        $usuario = User::factory()->rol('colaborador')->create(['name' => 'Viejo Nombre']);

        Livewire::test(UsuariosIndex::class)
            ->call('seleccionar', $usuario->id)
            ->set('nombre', 'Nuevo Nombre')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame('Nuevo Nombre', $usuario->refresh()->name);
    }

    public function test_editar_con_password_la_actualiza(): void
    {
        $this->actuarComoAdmin();
        $usuario = User::factory()->rol('colaborador')->create();

        Livewire::test(UsuariosIndex::class)
            ->call('seleccionar', $usuario->id)
            ->set('password', 'nuevaClave123')
            ->set('password_confirmation', 'nuevaClave123')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('nuevaClave123', $usuario->refresh()->password));
    }

    public function test_sube_foto_de_perfil(): void
    {
        Storage::fake('public');
        $this->actuarComoAdmin();

        Livewire::test(UsuariosIndex::class)
            ->call('nuevo')
            ->set('nombre', 'Con Foto')
            ->set('email', 'confoto@mallasarica.cl')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('foto', UploadedFile::fake()->image('avatar.jpg'))
            ->call('guardar')
            ->assertHasNoErrors();

        $usuario = User::firstWhere('email', 'confoto@mallasarica.cl');
        $this->assertNotNull($usuario->foto_path);
        Storage::disk('public')->assertExists($usuario->foto_path);
    }

    public function test_no_se_puede_crear_otro_super_admin(): void
    {
        $this->actuarComoAdmin();

        Livewire::test(UsuariosIndex::class)
            ->call('nuevo')
            ->set('nombre', 'Segundo Super')
            ->set('email', 'segundo@mallasarica.cl')
            ->set('rol', 'super_admin')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('guardar')
            ->assertHasErrors('rol');

        $this->assertDatabaseMissing('users', ['email' => 'segundo@mallasarica.cl']);
    }

    public function test_no_se_puede_cambiar_el_rol_del_super_admin(): void
    {
        $superAdmin = $this->actuarComoAdmin();

        Livewire::test(UsuariosIndex::class)
            ->call('seleccionar', $superAdmin->id)
            ->set('rol', 'colaborador')
            ->call('guardar')
            ->assertHasErrors('rol');

        $this->assertSame('super_admin', $superAdmin->refresh()->rol);
    }

    public function test_no_se_puede_eliminar_al_super_admin(): void
    {
        $admin = $this->actuarComoAdmin();
        $otro = User::factory()->rol('super_admin')->create();

        // Un segundo registro con rol super_admin no debería existir en la
        // práctica (el guardar lo impide), pero si ya existe en BD, eliminar
        // sigue bloqueado por rol, no por sesión.
        Livewire::test(UsuariosIndex::class)
            ->call('seleccionar', $otro->id)
            ->call('eliminar');

        $this->assertDatabaseHas('users', ['id' => $otro->id]);
        $this->assertNotSame($admin->id, $otro->id);
    }

    public function test_no_se_puede_eliminar_el_propio_usuario(): void
    {
        $admin = $this->actuarComoUsuario('administrador');

        Livewire::test(UsuariosIndex::class)
            ->call('seleccionar', $admin->id)
            ->call('eliminar');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_eliminar_un_usuario_normal_funciona(): void
    {
        $this->actuarComoAdmin();
        $usuario = User::factory()->rol('colaborador')->create();

        Livewire::test(UsuariosIndex::class)
            ->call('seleccionar', $usuario->id)
            ->call('eliminar');

        $this->assertDatabaseMissing('users', ['id' => $usuario->id]);
    }

    public function test_busqueda_filtra_por_nombre_o_correo(): void
    {
        $this->actuarComoAdmin();
        User::factory()->rol('colaborador')->create(['name' => 'Carlos Ruiz', 'email' => 'carlos@x.cl']);
        User::factory()->rol('colaborador')->create(['name' => 'Beto Silva', 'email' => 'beto@x.cl']);

        Livewire::test(UsuariosIndex::class)
            ->set('buscar', 'Carlos')
            ->assertSee('Carlos Ruiz')
            ->assertDontSee('Beto Silva');
    }

    public function test_email_principal_debe_ser_del_dominio_corporativo(): void
    {
        $this->actuarComoAdmin();

        Livewire::test(UsuariosIndex::class)
            ->call('nuevo')
            ->set('nombre', 'Externo')
            ->set('email', 'externo@gmail.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('guardar')
            ->assertHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'externo@gmail.com']);
    }

    public function test_vincula_un_correo_de_google_al_crear_usuario(): void
    {
        $this->actuarComoAdmin();

        Livewire::test(UsuariosIndex::class)
            ->call('nuevo')
            ->set('nombre', 'Con Gmail')
            ->set('email', 'congmail@mallasarica.cl')
            ->set('emailGoogle', 'congmail@gmail.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'congmail@mallasarica.cl', 'email_google' => 'congmail@gmail.com']);
    }

    public function test_correo_de_google_duplicado_no_guarda(): void
    {
        $this->actuarComoAdmin();
        User::factory()->rol('colaborador')->create(['email_google' => 'ocupado@gmail.com']);

        Livewire::test(UsuariosIndex::class)
            ->call('nuevo')
            ->set('nombre', 'Otro')
            ->set('email', 'otro@mallasarica.cl')
            ->set('emailGoogle', 'ocupado@gmail.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('guardar')
            ->assertHasErrors('emailGoogle');
    }

    public function test_correo_de_google_no_puede_ser_igual_al_principal(): void
    {
        $this->actuarComoAdmin();

        Livewire::test(UsuariosIndex::class)
            ->call('nuevo')
            ->set('nombre', 'Igual')
            ->set('email', 'igual@mallasarica.cl')
            ->set('emailGoogle', 'igual@mallasarica.cl')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('guardar')
            ->assertHasErrors('emailGoogle');
    }
}
