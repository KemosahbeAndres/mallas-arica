<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tarifa extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_espacio_id',
        'tramo_altura_id',
        'precio_ml_min',
        'precio_ml_max',
        'vigente_desde',
        'vigente_hasta',
    ];

    protected function casts(): array
    {
        return [
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }

    public function tipoEspacio(): BelongsTo
    {
        return $this->belongsTo(TipoEspacio::class);
    }

    public function tramoAltura(): BelongsTo
    {
        return $this->belongsTo(TramoAltura::class);
    }
}
