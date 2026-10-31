<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Empresa que emite una cotización (datos del encabezado y pie del PDF). */
class Empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'nombre',
        'subtitulo',
        'rut',
        'direccion',
        'telefono',
        'email',
        'predeterminada',
    ];

    protected function casts(): array
    {
        return ['predeterminada' => 'boolean'];
    }

    /** La marcada como predeterminada; si no hay ninguna, la más antigua. */
    public static function predeterminada(): ?self
    {
        return static::where('predeterminada', true)->first()
            ?? static::orderBy('id')->first();
    }

    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class);
    }
}
