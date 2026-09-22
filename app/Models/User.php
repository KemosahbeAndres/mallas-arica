<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROL_SUPER_ADMIN = 'super_admin';

    /** Dominio obligatorio del email principal (identidad corporativa). */
    public const DOMINIO_CORPORATIVO = 'mallasarica.cl';

    public const ROLES = ['super_admin', 'administrador', 'supervisor', 'colaborador'];

    public const ROLES_LABELS = [
        'super_admin' => 'Super Administrador',
        'administrador' => 'Administrador',
        'supervisor' => 'Supervisor',
        'colaborador' => 'Colaborador',
    ];

    /**
     * Roles con acceso al CRUD de usuarios (crear, editar, eliminar a otros).
     *
     * @var list<string>
     */
    public const ROLES_GESTIONAN_USUARIOS = ['super_admin', 'administrador'];

    /**
     * Roles que gestionan Clientes y Cotizaciones (crear/editar, no eliminar).
     *
     * @var list<string>
     */
    public const ROLES_GESTIONAN_CLIENTES_Y_COTIZACIONES = ['super_admin', 'administrador', 'supervisor'];

    /**
     * Roles que agendan eventos y asignan/reasignan colaboradores en las OT.
     *
     * @var list<string>
     */
    public const ROLES_AGENDAN_TRABAJOS = ['super_admin', 'administrador', 'supervisor'];

    /**
     * Roles que pueden eliminar registros (clientes, cotizaciones, fotos, etc).
     *
     * @var list<string>
     */
    public const ROLES_ELIMINAN = ['super_admin', 'administrador'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_google',
        'telefono',
        'foto_path',
        'rol',
        'google_id',
        'google_token',
        'google_refresh_token',
        'google_token_expires_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'google_token' => 'encrypted',
            'google_refresh_token' => 'encrypted',
            'google_token_expires_at' => 'datetime',
        ];
    }

    /** El usuario ya autorizó el scope de Calendar y tiene refresh token utilizable. */
    public function tieneGoogleCalendarConectado(): bool
    {
        return filled($this->google_refresh_token);
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_path ? Storage::disk('public')->url($this->foto_path) : null;
    }

    public function getRolLabelAttribute(): string
    {
        return self::ROLES_LABELS[$this->rol] ?? $this->rol;
    }

    public function esSuperAdmin(): bool
    {
        return $this->rol === self::ROL_SUPER_ADMIN;
    }

    public function gestionaUsuarios(): bool
    {
        return in_array($this->rol, self::ROLES_GESTIONAN_USUARIOS, true);
    }

    public function puedeGestionarClientesYCotizaciones(): bool
    {
        return in_array($this->rol, self::ROLES_GESTIONAN_CLIENTES_Y_COTIZACIONES, true);
    }

    public function puedeAgendarYAsignarTrabajos(): bool
    {
        return in_array($this->rol, self::ROLES_AGENDAN_TRABAJOS, true);
    }

    public function puedeEliminar(): bool
    {
        return in_array($this->rol, self::ROLES_ELIMINAN, true);
    }

    public function esColaborador(): bool
    {
        return $this->rol === 'colaborador';
    }

    /** Regla de validación reutilizable: el email principal es siempre @mallasarica.cl. */
    public static function reglaEmailCorporativo(): string
    {
        return 'ends_with:@'.self::DOMINIO_CORPORATIVO;
    }
}
