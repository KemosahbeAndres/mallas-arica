<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TrabajoFoto extends Model
{
    use HasFactory;

    protected $table = 'trabajo_fotos';

    protected $fillable = [
        'trabajo_id',
        'foto_path',
        'subida_por',
    ];

    public function trabajo(): BelongsTo
    {
        return $this->belongsTo(Trabajo::class);
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subida_por');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->foto_path);
    }
}
