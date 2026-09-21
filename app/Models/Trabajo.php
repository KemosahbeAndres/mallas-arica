<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * OT (Orden de Trabajo) — CLAUDE.md §11 ter. Salida a terreno con un objetivo.
 * Se crea al aceptar una cotización. Versión mínima del Sprint 12 (sin medidas /
 * firma / consumos, que son Etapa 2). Las fotos de evidencia sí se agregaron
 * (permisos por rol, ver TrabajoService).
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
        'cantidad_ventanas',
        'cantidad_balcones',
        'estado',
        'meses_mantencion',
        'finalizado_at',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_ventanas' => 'integer',
            'cantidad_balcones' => 'integer',
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

    public function colaboradores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'trabajo_usuario');
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(TrabajoFoto::class);
    }

    /** ¿La mantención venció? Solo aplica a OT ejecutadas. */
    public function getMantencionVencidaAttribute(): bool
    {
        return $this->estado === 'ejecutada'
            && $this->finalizado_at !== null
            && $this->finalizado_at->copy()->addMonths($this->meses_mantencion)->isPast();
    }

    /** Fotos mínimas exigidas: 1 por ventana, 2 por balcón. */
    public function minimoFotos(): int
    {
        return $this->cantidad_ventanas * 1 + $this->cantidad_balcones * 2;
    }

    public function cumpleMinimoFotos(): bool
    {
        return $this->fotos()->count() >= $this->minimoFotos();
    }
}
