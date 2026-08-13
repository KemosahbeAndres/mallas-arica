<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Cotizacion extends Model
{
    use HasFactory;

    protected $table = 'cotizaciones';

    protected $fillable = [
        'uuid',
        'codigo',
        'nombre',
        'telefono',
        'email',
        'comuna',
        'canal',
        'estado',
        'total_min',
        'total_max',
        'requiere_visita',
        'utm_source',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'requiere_visita' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Cotizacion $cotizacion) {
            $cotizacion->uuid ??= (string) Str::uuid();
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(CotizacionItem::class);
    }

    public function visita(): HasOne
    {
        return $this->hasOne(Visita::class);
    }
}
