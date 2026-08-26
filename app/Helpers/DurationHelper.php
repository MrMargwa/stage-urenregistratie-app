<?php

namespace App\Helpers;

class DurationHelper
{
    public static function formatMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public static function formatSeconds(int $seconds): string
    {
        return self::formatMinutes((int) round($seconds / 60));
    }
}
