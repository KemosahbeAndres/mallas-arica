<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MediaItem extends Model
{
    protected $table = 'media_items';

    protected $fillable = [
        'archivo_path',
        'titulo',
        'media_album_id',
        'orden',
    ];

    public function album(): BelongsTo
    {
        return $this->belongsTo(MediaAlbum::class, 'media_album_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->archivo_path);
    }
}
