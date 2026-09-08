<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Formato de fechas en español sin depender del locale de la app (APP_LOCALE=en).
 * Centraliza los nombres de meses/días que antes vivían sueltos en el PDF.
 */
class FechaEsp
{
    public const MESES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    public const DIAS = [
        0 => 'domingo', 1 => 'lunes', 2 => 'martes', 3 => 'miércoles',
        4 => 'jueves', 5 => 'viernes', 6 => 'sábado',
    ];

    public const DIAS_CORTOS = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

    public static function mes(int $mes): string
    {
        return self::MESES[$mes];
    }

    /** ej. "septiembre de 2026" */
    public static function mesAnio(CarbonInterface $fecha): string
    {
        return self::MESES[$fecha->month].' de '.$fecha->year;
    }

    /** ej. "3 de septiembre de 2026" */
    public static function largo(CarbonInterface $fecha): string
    {
        return $fecha->day.' de '.self::MESES[$fecha->month].' de '.$fecha->year;
    }

    /** ej. "jue, 3 sept" */
    public static function corto(CarbonInterface $fecha): string
    {
        $dia = ucfirst(mb_substr(self::DIAS[$fecha->dayOfWeek], 0, 3));
        $mesCorto = mb_substr(self::MESES[$fecha->month], 0, 4);

        return "{$dia}, {$fecha->day} {$mesCorto}";
    }

    /** ej. "Jueves, 3 de septiembre" */
    public static function diaMes(CarbonInterface $fecha): string
    {
        return ucfirst(self::DIAS[$fecha->dayOfWeek]).', '.$fecha->day.' de '.self::MESES[$fecha->month];
    }
}
