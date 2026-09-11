<?php

use App\Models\TimeEntry;
use App\Models\User;

it('telt een afwezigheidsdag niet mee als stage-uren', function () {
    $user = User::factory()->create();

    $entry = TimeEntry::create([
        'user_id' => $user->id,
        'date' => '2026-09-09',
        'is_absent' => true,
        'description' => 'Ziek',
    ]);

    expect($entry->isAbsent())->toBeTrue()
        ->and($entry->duration_minutes)->toBe(0)
        ->and($user->fresh()->totalLoggedMinutes())->toBe(0);
});

it('negeert tijden op een afwezigheidsdag', function () {
    $user = User::factory()->create();

    $entry = TimeEntry::create([
        'user_id' => $user->id,
        'date' => '2026-09-09',
        'start_time' => '09:00',
        'end_time' => '17:00',
        'break_minutes' => 30,
        'is_absent' => true,
    ]);

    expect($entry->duration_minutes)->toBe(0);
});
