<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingMediaSlot extends Model
{
    protected $table = 'landing_media_slots';

    public const ENCUADRES = ['cover', 'contain', 'fill'];

    protected $attributes = [
        'encuadre' => 'cover',
        'posicion_x' => 50,
        'posicion_y' => 50,
    ];

    protected $fillable = [
        'slug',
        'media_item_id',
        'media_album_id',
        'encuadre',
        'posicion_x',
        'posicion_y',
    ];

    protected function casts(): array
    {
        return [
            'posicion_x' => 'integer',
            'posicion_y' => 'integer',
        ];
    }

    public function mediaItem(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class);
    }

    public function mediaAlbum(): BelongsTo
    {
        return $this->belongsTo(MediaAlbum::class);
    }

    /**
     * CSS inline listo para usar en style="{{ $slot->estiloImagen }}".
     */
    public function getEstiloImagenAttribute(): string
    {
        $fit = in_array($this->encuadre, self::ENCUADRES, true) ? $this->encuadre : 'cover';

        return "object-fit: {$fit}; object-position: {$this->posicion_x}% {$this->posicion_y}%;";
    }
}
