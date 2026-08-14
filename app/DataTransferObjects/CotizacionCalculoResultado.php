<?php

namespace App\DataTransferObjects;

class CotizacionCalculoResultado
{
    /** @param ItemCalculoResultado[] $items */
    public function __construct(
        public readonly array $items,
        public readonly int $totalMin,
        public readonly int $totalMax,
        public readonly bool $requiereVisita,
    ) {}
}
