<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingMediaSlot extends Model
{
    protected $table = 'landing_media_slots';

    protected $fillable = [
        'slug',
        'media_item_id',
        'media_album_id',
    ];

    public function mediaItem(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class);
    }

    public function mediaAlbum(): BelongsTo
    {
        return $this->belongsTo(MediaAlbum::class);
    }
}
