<?php

use App\Helpers\DurationHelper;

it('formatteert minuten naar HH:MM', function () {
    expect(DurationHelper::formatMinutes(0))->toBe('00:00')
        ->and(DurationHelper::formatMinutes(59))->toBe('00:59')
        ->and(DurationHelper::formatMinutes(60))->toBe('01:00')
        ->and(DurationHelper::formatMinutes(450))->toBe('07:30')
        ->and(DurationHelper::formatMinutes(1440))->toBe('24:00');
})->name('DurationHelper formatMinutes');

it('berekent de duur in minuten van begin- tot eindtijd minus pauze', function () {
    expect(DurationHelper::toMinutes('09:00', '17:00'))->toBe(480)
        ->and(DurationHelper::toMinutes('09:00', '17:00', 30))->toBe(450)
        ->and(DurationHelper::toMinutes('00:00', '00:00'))->toBe(0);
})->name('DurationHelper toMinutes basis');

it('levert 0 minuten bij een ongeldige (eind vóór begin) combinatie', function () {
    expect(DurationHelper::toMinutes('17:00', '09:00'))->toBe(0)
        ->and(DurationHelper::toMinutes('17:00', '09:00', 30))->toBe(0);
})->name('DurationHelper toMinutes ongeldig');
