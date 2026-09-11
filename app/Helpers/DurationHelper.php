<?php

namespace App\Helpers;

class DurationHelper
{
    /**
     * Berekent de netto duur (eindtijd − begintijd − pauze) in minuten.
     *
     * Werkt op 'H:i'-tijdstrings zodat dezelfde formule overal kan worden
     * gebruikt (models én migraties) zonder dat de logica uit elkaar loopt.
     * De geldigheid van de tijden wordt gegarandeerd door de validatie in
     * TimeEntry::boot() (eindtijd mag niet vóór begintijd liggen); een
     * ongeldige combinatie levert hier simpelweg 0 minuten op.
     */
    public static function toMinutes(string $startTime, string $endTime, int $breakMinutes = 0): int
    {
        [$startH, $startM] = array_map('intval', explode(':', $startTime));
        [$endH, $endM] = array_map('intval', explode(':', $endTime));

        $start = ($startH * 60) + $startM;
        $end = ($endH * 60) + $endM;

        return max(0, ($end - $start) - $breakMinutes);
    }

    /**
     * Formatteert een aantal minuten als 'HH:MM'.
     */
    public static function formatMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * Formatteert een aantal minuten als afgerond aantal hele uren, bijv. '8 uur'.
     */
    public static function formatHours(int $minutes): string
    {
        return round($minutes / 60).' uur';
    }
}
