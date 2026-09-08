<?php

namespace App\Helpers;

class DurationHelper
{
    /**
     * Berekent de netto duur (eindtijd − begintijd − pauze) in minuten.
     *
     * Werkt op 'H:i'-tijdstrings zodat dezelfde formule overal kan worden
     * gebruikt (models én migraties) zonder dat de logica uit elkaar loopt.
     * Rondt middernacht af: als de eindtijd vóór de begintijd ligt, wordt
     * aangenomen dat het blok de volgende dag eindigt (+24 uur).
     */
    public static function toMinutes(string $startTime, string $endTime, int $breakMinutes = 0): int
    {
        [$startH, $startM] = array_map('intval', explode(':', $startTime));
        [$endH, $endM] = array_map('intval', explode(':', $endTime));

        $start = ($startH * 60) + $startM;
        $end = ($endH * 60) + $endM;

        $minutes = $end - $start;

        if ($minutes < 0) {
            $minutes += 1440;
        }

        return max(0, $minutes - $breakMinutes);
    }

    /**
     * Formatteert een aantal minuten als 'HH:MM'.
     */
    public static function formatMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * Formatteert een aantal seconden (afgerond op minuten) als 'HH:MM'.
     */
    public static function formatSeconds(int $seconds): string
    {
        return self::formatMinutes((int) round($seconds / 60));
    }
}
