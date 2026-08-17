<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GaleriaItem extends Model
{
    use HasFactory;

    protected $table = 'galeria_items';

    protected $fillable = [
        'foto_path',
        'titulo',
        'tipo_espacio_id',
        'orden',
        'publicado',
    ];

    protected function casts(): array
    {
        return [
            'publicado' => 'boolean',
        ];
    }

    public function tipoEspacio(): BelongsTo
    {
        return $this->belongsTo(TipoEspacio::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->foto_path);
    }
}
