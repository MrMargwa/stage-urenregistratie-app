<?php

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Validation\ValidationException;

it('blokkeert een overlappende registratie op dezelfde dag', function () {
    $user = User::factory()->create();

    TimeEntry::factory()->for($user)->create([
        'date' => '2026-08-26',
        'start_time' => '09:00',
        'end_time' => '12:00',
        'break_minutes' => 0,
    ]);

    expect(fn () => TimeEntry::factory()->for($user)->create([
        'date' => '2026-08-26',
        'start_time' => '11:00',
        'end_time' => '13:00',
        'break_minutes' => 0,
    ]))->toThrow(ValidationException::class);
});

it('staat een niet-overlappende registratie op dezelfde dag toe', function () {
    $user = User::factory()->create();

    TimeEntry::factory()->for($user)->create([
        'date' => '2026-08-26',
        'start_time' => '09:00',
        'end_time' => '12:00',
        'break_minutes' => 0,
    ]);

    $entry = TimeEntry::factory()->for($user)->create([
        'date' => '2026-08-26',
        'start_time' => '13:00',
        'end_time' => '17:00',
        'break_minutes' => 0,
    ]);

    expect($entry->exists)->toBeTrue();
});

it('staat hetzelfde tijdsblok bij verschillende gebruikers toe', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    TimeEntry::factory()->for($userA)->create([
        'date' => '2026-08-26',
        'start_time' => '09:00',
        'end_time' => '17:00',
    ]);

    $entry = TimeEntry::factory()->for($userB)->create([
        'date' => '2026-08-26',
        'start_time' => '09:00',
        'end_time' => '17:00',
    ]);

    expect($entry->exists)->toBeTrue();
});

it('staat een update toe die geen overlap veroorzaakt met zichzelf', function () {
    $user = User::factory()->create();

    $entry = TimeEntry::factory()->for($user)->create([
        'date' => '2026-08-26',
        'start_time' => '09:00',
        'end_time' => '17:00',
        'break_minutes' => 0,
    ]);

    $entry->update(['end_time' => '18:00']);

    expect($entry->refresh()->end_time->format('H:i'))->toBe('18:00');
});
