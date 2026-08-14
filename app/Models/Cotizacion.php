<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Cotizacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cotizaciones';

    protected $fillable = [
        'uuid',
        'nombre',
        'telefono',
        'email',
        'direccion',
        'canal',
        'estado',
        'total_min',
        'total_max',
        'requiere_visita',
        'utm_source',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'requiere_visita' => 'boolean',
        ];
    }

    /**
     * Número correlativo público de la cotización (ej. "0047"), derivado del id.
     * Reemplaza al antiguo código alfanumérico MA-XXXX: es el único identificador
     * que se muestra al cliente (WhatsApp, PDF, pantalla de confirmación).
     */
    protected function numero(): Attribute
    {
        return Attribute::make(
            get: fn () => str_pad((string) $this->id, 4, '0', STR_PAD_LEFT),
        );
    }

    protected static function booted(): void
    {
        static::creating(function (Cotizacion $cotizacion) {
            $cotizacion->uuid ??= (string) Str::uuid();
        });

        // El cascadeOnDelete() de la FK no dispara con soft deletes (no hay
        // DELETE real). Se replica la cascada a mano para items y visita.
        static::deleting(function (Cotizacion $cotizacion) {
            if ($cotizacion->isForceDeleting()) {
                return;
            }

            $cotizacion->items()->delete();
            $cotizacion->visita()->delete();
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(CotizacionItem::class);
    }

    public function visita(): HasOne
    {
        return $this->hasOne(Visita::class);
    }
}
