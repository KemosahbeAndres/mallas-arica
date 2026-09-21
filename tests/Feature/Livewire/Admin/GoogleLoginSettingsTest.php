<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Ajustes\GoogleLoginSettings;
use App\Models\Configuracion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class GoogleLoginSettingsTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    private function jsonGoogle(string $clientId = 'abc.apps.googleusercontent.com', string $secret = 'GOCSPX-secreto'): UploadedFile
    {
        $contenido = json_encode([
            'web' => [
                'client_id' => $clientId,
                'client_secret' => $secret,
                'redirect_uris' => ['https://admin.mallasarica.cl/login/google/callback'],
            ],
        ]);

        return UploadedFile::fake()->createWithContent('google.json', $contenido);
    }

    public function test_super_admin_sube_el_json_y_queda_configurado(): void
    {
        $this->actuarComoAdmin();

        Livewire::test(GoogleLoginSettings::class)
            ->set('archivo', $this->jsonGoogle())
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame('abc.apps.googleusercontent.com', Configuracion::obtener('google_oauth.client_id'));
        $this->assertSame('GOCSPX-secreto', Configuracion::obtener('google_oauth.client_secret'));
    }

    public function test_client_secret_queda_cifrado_en_bd(): void
    {
        $this->actuarComoAdmin();

        Livewire::test(GoogleLoginSettings::class)
            ->set('archivo', $this->jsonGoogle())
            ->call('guardar');

        $crudo = DB::table('configuraciones')->where('key', 'google_oauth.client_secret')->value('valor');
        $this->assertStringNotContainsString('GOCSPX-secreto', $crudo);
    }

    public function test_json_sin_client_id_o_secret_muestra_error(): void
    {
        $this->actuarComoAdmin();
        $archivo = UploadedFile::fake()->createWithContent('malo.json', json_encode(['web' => ['foo' => 'bar']]));

        Livewire::test(GoogleLoginSettings::class)
            ->set('archivo', $archivo)
            ->call('guardar')
            ->assertHasErrors('archivo');

        $this->assertNull(Configuracion::obtener('google_oauth.client_id'));
    }

    public function test_eliminar_borra_la_configuracion(): void
    {
        $this->actuarComoAdmin();
        Configuracion::guardar('google_oauth.client_id', 'x');
        Configuracion::guardar('google_oauth.client_secret', 'y');

        Livewire::test(GoogleLoginSettings::class)->call('eliminar');

        $this->assertNull(Configuracion::obtener('google_oauth.client_id'));
        $this->assertNull(Configuracion::obtener('google_oauth.client_secret'));
    }

    public function test_administrador_no_puede_acceder(): void
    {
        $this->actuarComoUsuario('administrador');

        Livewire::test(GoogleLoginSettings::class)->assertForbidden();
    }

    public function test_supervisor_no_puede_acceder(): void
    {
        $this->actuarComoUsuario('supervisor');

        Livewire::test(GoogleLoginSettings::class)->assertForbidden();
    }
}
