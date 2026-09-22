<?php

namespace App\Livewire\Admin\Usuarios;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class UsuariosIndex extends Component
{
    use WithFileUploads;

    #[Url]
    public ?int $seleccionado = null;

    public string $buscar = '';

    // --- Formulario del usuario seleccionado ---
    public string $nombre = '';

    public string $email = '';

    public string $emailGoogle = '';

    public string $telefono = '';

    public string $rol = 'colaborador';

    public string $password = '';

    public string $password_confirmation = '';

    public $foto = null;

    public ?string $guardado = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->gestionaUsuarios(), 403);

        if ($this->seleccionado && User::whereKey($this->seleccionado)->exists()) {
            $this->cargar($this->seleccionado);
        } else {
            $this->seleccionado = null;
        }
    }

    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                User::reglaEmailCorporativo(),
                Rule::unique('users', 'email')->ignore($this->seleccionado),
            ],
            'emailGoogle' => [
                'nullable', 'email', 'max:255',
                Rule::unique('users', 'email_google')->ignore($this->seleccionado),
                Rule::notIn([trim($this->email)]),
            ],
            'telefono' => ['nullable', 'string', 'max:40'],
            'rol' => ['required', Rule::in(User::ROLES)],
            'password' => [$this->seleccionado ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.ends_with' => 'El correo principal debe ser una dirección @'.User::DOMINIO_CORPORATIVO.'.',
            'email.unique' => 'Ese correo ya está en uso por otro usuario.',
            'emailGoogle.unique' => 'Ese correo de Google ya está vinculado a otro usuario.',
            'emailGoogle.not_in' => 'El correo de Google debe ser distinto al correo principal.',
            'password.required' => 'La contraseña es obligatoria para un usuario nuevo.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ];
    }

    public function validationAttributes(): array
    {
        return ['password' => 'contraseña'];
    }

    #[Computed]
    public function usuarioSeleccionado(): ?User
    {
        return $this->seleccionado ? User::find($this->seleccionado) : null;
    }

    #[Computed]
    public function usuarios()
    {
        return User::query()
            ->when($this->buscar !== '', function ($q) {
                $termino = '%'.$this->buscar.'%';
                $q->where(fn ($sub) => $sub
                    ->where('name', 'like', $termino)
                    ->orWhere('email', 'like', $termino));
            })
            ->orderBy('name')
            ->get();
    }

    public function nuevo(): void
    {
        $this->seleccionado = null;
        $this->reset(['nombre', 'email', 'emailGoogle', 'telefono', 'password', 'password_confirmation', 'foto', 'guardado']);
        $this->rol = 'colaborador';
        $this->resetValidation();
        unset($this->usuarioSeleccionado);
    }

    public function seleccionar(int $id): void
    {
        $this->cargar($id);
    }

    private function cargar(int $id): void
    {
        $usuario = User::findOrFail($id);

        $this->seleccionado = $usuario->id;
        $this->nombre = $usuario->name;
        $this->email = $usuario->email;
        $this->emailGoogle = (string) $usuario->email_google;
        $this->telefono = (string) $usuario->telefono;
        $this->rol = $usuario->rol;
        $this->password = '';
        $this->password_confirmation = '';
        $this->foto = null;
        $this->guardado = null;
        $this->resetValidation();
        unset($this->usuarioSeleccionado);
    }

    public function guardar(): void
    {
        $usuario = $this->seleccionado ? User::findOrFail($this->seleccionado) : new User;

        if ($usuario->exists && $usuario->esSuperAdmin() && $this->rol !== User::ROL_SUPER_ADMIN) {
            $this->addError('rol', 'El Super Administrador no puede cambiar de rol.');

            return;
        }

        if (! $usuario->exists && $this->rol === User::ROL_SUPER_ADMIN) {
            $this->addError('rol', 'No se puede crear otro Super Administrador.');

            return;
        }

        $this->validate();

        $usuario->fill([
            'name' => trim($this->nombre),
            'email' => trim($this->email),
            'email_google' => trim($this->emailGoogle) ?: null,
            'telefono' => trim($this->telefono) ?: null,
            'rol' => $this->rol,
        ]);

        if ($this->password !== '') {
            $usuario->password = Hash::make($this->password);
        }

        if ($this->foto) {
            $usuario->foto_path = $this->foto->store('usuarios', 'public');
        }

        $usuario->save();

        $this->seleccionado = $usuario->id;

        unset($this->usuarios);
        $this->cargar($this->seleccionado);
        $this->guardado = 'Usuario guardado.';
    }

    public function eliminar(): void
    {
        if (! $this->seleccionado) {
            return;
        }

        if ($this->seleccionado === auth()->id()) {
            $this->addError('rol', 'No puedes eliminar tu propio usuario.');

            return;
        }

        $usuario = User::findOrFail($this->seleccionado);

        if ($usuario->esSuperAdmin()) {
            $this->addError('rol', 'El Super Administrador no se puede eliminar.');

            return;
        }

        $usuario->delete();

        unset($this->usuarios);
        $this->nuevo();
    }

    public function render()
    {
        return view('livewire.admin.usuarios.usuarios-index')
            ->layout('components.layouts.admin', [
                'title' => 'Usuarios',
                'subtitle' => 'Accesos al panel administrativo',
            ]);
    }
}
