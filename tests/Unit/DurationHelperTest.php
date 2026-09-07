<?php

use App\Helpers\DurationHelper;

it('formatteert minuten naar HH:MM', function () {
    expect(DurationHelper::formatMinutes(0))->toBe('00:00')
        ->and(DurationHelper::formatMinutes(59))->toBe('00:59')
        ->and(DurationHelper::formatMinutes(60))->toBe('01:00')
        ->and(DurationHelper::formatMinutes(450))->toBe('07:30')
        ->and(DurationHelper::formatMinutes(1440))->toBe('24:00');
})->name('DurationHelper formatMinutes');

it('formatteert seconden correct via formatSeconds', function () {
    expect(DurationHelper::formatSeconds(2700))->toBe('00:45')
        ->and(DurationHelper::formatSeconds(5400))->toBe('01:30');
})->name('DurationHelper formatSeconds');
