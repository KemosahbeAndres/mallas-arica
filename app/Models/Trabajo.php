<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * OT (Orden de Trabajo) — CLAUDE.md §11 ter. Salida a terreno con un objetivo.
 * Se crea al aceptar una cotización. Versión mínima del Sprint 12 (sin medidas /
 * firma / fotos / consumos, que son Etapa 2).
 */
class Trabajo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trabajos';

    public const ESTADOS = ['pendiente', 'en_curso', 'ejecutada', 'cancelada'];

    protected $fillable = [
        'cliente_id',
        'cliente_direccion_id',
        'cotizacion_id',
        'evento_id',
        'titulo',
        'descripcion',
        'estado',
        'meses_mantencion',
        'finalizado_at',
    ];

    protected function casts(): array
    {
        return [
            'meses_mantencion' => 'integer',
            'finalizado_at' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function clienteDireccion(): BelongsTo
    {
        return $this->belongsTo(ClienteDireccion::class);
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }

    /** ¿La mantención venció? Solo aplica a OT ejecutadas. */
    public function getMantencionVencidaAttribute(): bool
    {
        return $this->estado === 'ejecutada'
            && $this->finalizado_at !== null
            && $this->finalizado_at->copy()->addMonths($this->meses_mantencion)->isPast();
    }
}
