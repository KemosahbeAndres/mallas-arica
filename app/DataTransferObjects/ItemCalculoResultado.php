<?php

namespace App\DataTransferObjects;

class ItemCalculoResultado
{
    public function __construct(
        public readonly int $tipoEspacioId,
        public readonly ?int $tipoMallaId,
        public readonly ?int $tramoAlturaId,
        public readonly float $metrosLineales,
        public readonly bool $requiereVisita,
        public readonly bool $esLead,
        public readonly ?int $precioMlMinSnapshot = null,
        public readonly ?int $precioMlMaxSnapshot = null,
        public readonly ?float $multiplicadorSnapshot = null,
        public readonly int $subtotalMin = 0,
        public readonly int $subtotalMax = 0,
        public readonly ?string $motivoLead = null,
    ) {}
}
