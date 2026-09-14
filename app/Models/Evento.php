<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'eventos';

    public const TIPOS = ['terreno', 'oficina'];

    public const ESTADOS = ['agendado', 'hecho', 'cancelado'];

    protected $fillable = [
        'titulo',
        'descripcion',
        'tipo',
        'estado',
        'inicio',
        'fin',
        'todo_el_dia',
        'cliente_id',
        'ubicacion',
        'notas',
        'google_event_id',
    ];

    protected function casts(): array
    {
        return [
            'inicio' => 'datetime',
            'fin' => 'datetime',
            'todo_el_dia' => 'boolean',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** Una OT apunta a su evento vía `trabajos.evento_id` — relación inversa. */
    public function trabajo(): HasOne
    {
        return $this->hasOne(Trabajo::class);
    }

    public function scopeEntre(Builder $query, \DateTimeInterface $desde, \DateTimeInterface $hasta): Builder
    {
        return $query->where('inicio', '>=', $desde)->where('inicio', '<=', $hasta);
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('estado', '!=', 'cancelado');
    }
}
