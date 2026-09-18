<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaAlbum extends Model
{
    protected $table = 'media_albums';

    protected $fillable = [
        'nombre',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MediaItem::class)->orderBy('orden');
    }
}
