<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TramoAltura extends Model
{
    use HasFactory;

    protected $table = 'tramos_altura';

    protected $fillable = [
        'etiqueta',
        'altura_min',
        'altura_max',
        'requiere_visita',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'altura_min' => 'decimal:2',
            'altura_max' => 'decimal:2',
            'requiere_visita' => 'boolean',
        ];
    }
}
