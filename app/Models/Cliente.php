<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'telefono',
        'email',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'notificado_at' => 'datetime',
        ];
    }

    public function direcciones(): HasMany
    {
        return $this->hasMany(ClienteDireccion::class);
    }

    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class);
    }

    public function trabajos(): HasMany
    {
        return $this->hasMany(Trabajo::class);
    }

    // El cascadeOnDelete() de la FK no dispara con soft deletes (no hay DELETE
    // real). Las direcciones no llevan SoftDeletes; al soft-borrar el cliente
    // se conservan colgando y se limpian solo en forceDelete (cascada de BD).
}
