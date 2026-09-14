<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GaleriaItem extends Model
{
    use HasFactory;

    protected $table = 'galeria_items';

    protected $fillable = [
        'foto_path',
        'titulo',
        'orden',
        'publicado',
    ];

    protected function casts(): array
    {
        return [
            'publicado' => 'boolean',
        ];
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->foto_path);
    }
}
