<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteDireccion extends Model
{
    use HasFactory;

    protected $table = 'cliente_direcciones';

    protected $fillable = [
        'cliente_id',
        'direccion',
        'etiqueta',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
