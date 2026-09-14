<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $table = 'faqs';

    protected $fillable = [
        'pregunta',
        'respuesta',
        'orden',
        'publicada',
    ];

    protected function casts(): array
    {
        return [
            'publicada' => 'boolean',
            'orden' => 'integer',
        ];
    }

    public function scopePublicadas(Builder $query): Builder
    {
        return $query->where('publicada', true)->orderBy('orden');
    }
}
