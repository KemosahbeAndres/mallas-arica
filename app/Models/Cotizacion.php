<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cotizacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cotizaciones';

    /** Flujo: borrador → generada → aceptada → rechazada (Sprint 12). */
    public const ESTADOS = ['borrador', 'generada', 'aceptada', 'rechazada'];

    public const IVA_TASA = 0.19;

    protected $fillable = [
        'cliente_id',
        'cliente_direccion_id',
        'nombre',
        'telefono',
        'email',
        'direccion',
        'estado',
        'descuento_pct',
        'total_min',
        'total_max',
    ];

    protected function casts(): array
    {
        return [
            'descuento_pct' => 'decimal:2',
            'notificado_at' => 'datetime',
        ];
    }

    /**
     * Folio público de la cotización (ej. "0047"), derivado del id. Único
     * identificador visible: PDF, listado del panel, encabezado de la ficha.
     * Antes se llamaba `numero` (Sprint 12 lo renombró a `folio`, como el mockup).
     */
    protected function folio(): Attribute
    {
        return Attribute::make(
            get: fn () => str_pad((string) $this->id, 4, '0', STR_PAD_LEFT),
        );
    }

    /** Neto = suma de subtotales de las líneas, menos el descuento global. */
    protected function neto(): Attribute
    {
        return Attribute::make(
            get: function () {
                $bruto = $this->items->sum('subtotal');

                return (int) round($bruto * (1 - ((float) $this->descuento_pct / 100)));
            },
        );
    }

    protected function iva(): Attribute
    {
        return Attribute::make(get: fn () => (int) round($this->neto * self::IVA_TASA));
    }

    protected function total(): Attribute
    {
        return Attribute::make(get: fn () => $this->neto + $this->iva);
    }

    protected static function booted(): void
    {
        // El cascadeOnDelete() de la FK no dispara con soft deletes (no hay
        // DELETE real). Se replica la cascada a mano para los items.
        static::deleting(function (Cotizacion $cotizacion) {
            if ($cotizacion->isForceDeleting()) {
                return;
            }

            $cotizacion->items()->delete();
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(CotizacionItem::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function clienteDireccion(): BelongsTo
    {
        return $this->belongsTo(ClienteDireccion::class);
    }

    public function trabajo(): HasOne
    {
        return $this->hasOne(Trabajo::class);
    }
}
