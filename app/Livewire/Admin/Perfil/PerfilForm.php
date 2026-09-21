<?php

namespace App\Livewire\Admin\Perfil;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class PerfilForm extends Component
{
    use WithFileUploads;

    public string $nombre = '';

    public string $email = '';

    public string $telefono = '';

    public string $passwordActual = '';

    public string $password = '';

    public string $password_confirmation = '';

    #[Validate('nullable|image|max:2048')]
    public $foto = null;

    public ?string $guardado = null;

    public function mount(): void
    {
        $usuario = Auth::user();

        $this->nombre = $usuario->name;
        $this->email = $usuario->email;
        $this->telefono = (string) $usuario->telefono;
    }

    protected function rules(): array
    {
        $usuario = Auth::user();

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'telefono' => ['nullable', 'string', 'max:40'],
            'passwordActual' => [$this->password !== '' ? 'required' : 'nullable', 'string', 'current_password:web'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.unique' => 'Ese correo ya está en uso por otro usuario.',
            'passwordActual.required' => 'Ingresa tu contraseña actual para definir una nueva.',
            'passwordActual.current_password' => 'La contraseña actual no es correcta.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'passwordActual' => 'contraseña actual',
            'password' => 'nueva contraseña',
        ];
    }

    public function guardar(): void
    {
        $this->validate();

        $usuario = Auth::user();

        $usuario->fill([
            'name' => trim($this->nombre),
            'email' => trim($this->email),
            'telefono' => trim($this->telefono) ?: null,
        ]);

        if ($this->password !== '') {
            $usuario->password = Hash::make($this->password);
        }

        if ($this->foto) {
            $usuario->foto_path = $this->foto->store('usuarios', 'public');
        }

        $usuario->save();

        $this->reset(['passwordActual', 'password', 'password_confirmation', 'foto']);
        $this->guardado = 'Perfil actualizado.';
    }

    public function render()
    {
        return view('livewire.admin.perfil.perfil-form')
            ->layout('components.layouts.admin', [
                'title' => 'Mi perfil',
                'subtitle' => 'Datos de tu cuenta',
            ]);
    }
}
