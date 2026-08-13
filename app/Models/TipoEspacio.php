<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoEspacio extends Model
{
    use HasFactory;

    protected $table = 'tipos_espacio';

    protected $fillable = [
        'slug',
        'nombre',
        'descripcion',
        'icono',
        'permite_calculo',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'permite_calculo' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function tarifas(): HasMany
    {
        return $this->hasMany(Tarifa::class);
    }
}
