<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotizacionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cotizacion_id',
        'tipo_espacio_id',
        'tipo_malla_id',
        'tramo_altura_id',
        'metros_lineales',
        'precio_ml_min_snapshot',
        'precio_ml_max_snapshot',
        'multiplicador_snapshot',
        'subtotal_min',
        'subtotal_max',
    ];

    protected function casts(): array
    {
        return [
            'metros_lineales' => 'decimal:2',
            'multiplicador_snapshot' => 'decimal:2',
        ];
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function tipoEspacio(): BelongsTo
    {
        return $this->belongsTo(TipoEspacio::class);
    }

    public function tipoMalla(): BelongsTo
    {
        return $this->belongsTo(TipoMalla::class);
    }

    public function tramoAltura(): BelongsTo
    {
        return $this->belongsTo(TramoAltura::class);
    }
}
