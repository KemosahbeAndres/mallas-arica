<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoMalla extends Model
{
    use HasFactory;

    protected $table = 'tipos_malla';

    protected $fillable = [
        'slug',
        'nombre',
        'grosor_mm',
        'rombo_cm',
        'multiplicador',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'grosor_mm' => 'decimal:2',
            'rombo_cm' => 'decimal:2',
            'multiplicador' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }
}
