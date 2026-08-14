<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visita extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cotizacion_id',
        'equipo_id',
        'fecha_agendada',
        'ventana_horaria',
        'direccion',
        'estado',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'fecha_agendada' => 'date',
        ];
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }
}
