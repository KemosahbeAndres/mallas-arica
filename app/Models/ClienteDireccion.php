<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClienteDireccion extends Model
{
    use HasFactory;

    protected $table = 'cliente_direcciones';

    protected $fillable = [
        'cliente_id',
        'direccion',
        'etiqueta',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class);
    }

    public function etiquetaCompleta(): string
    {
        return $this->etiqueta ? "{$this->etiqueta} — {$this->direccion}" : $this->direccion;
    }
}
