<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CotizacionItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cotizacion_id',
        'descripcion',
        'precio_unitario',
        'cantidad',
        'descuento_pct',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'descuento_pct' => 'decimal:2',
        ];
    }

    /** Subtotal de la línea: precio × cantidad, menos el descuento de línea. */
    public static function calcularSubtotal(int $precioUnitario, float $cantidad, float $descuentoPct): int
    {
        $bruto = $precioUnitario * $cantidad;

        return (int) round($bruto * (1 - ($descuentoPct / 100)));
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }
}
