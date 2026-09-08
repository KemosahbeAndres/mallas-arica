<?php

namespace Tests\Unit;

use App\Support\FechaEsp;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class FechaEspTest extends TestCase
{
    public function test_mes_anio(): void
    {
        $this->assertSame('septiembre de 2026', FechaEsp::mesAnio(Carbon::parse('2026-09-07')));
    }

    public function test_largo(): void
    {
        $this->assertSame('3 de septiembre de 2026', FechaEsp::largo(Carbon::parse('2026-09-03')));
    }

    public function test_corto(): void
    {
        // 2026-09-03 es jueves
        $this->assertSame('Jue, 3 sept', FechaEsp::corto(Carbon::parse('2026-09-03')));
    }

    public function test_dia_mes(): void
    {
        $this->assertSame('Jueves, 3 de septiembre', FechaEsp::diaMes(Carbon::parse('2026-09-03')));
    }
}
