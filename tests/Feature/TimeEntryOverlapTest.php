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

it('staat aaneengesloten registraties toe (einde = begin van volgende)', function () {
    $user = User::factory()->create();

    TimeEntry::factory()->for($user)->create([
        'date' => '2026-08-26',
        'start_time' => '09:00',
        'end_time' => '12:00',
        'break_minutes' => 0,
    ]);

    $entry = TimeEntry::factory()->for($user)->create([
        'date' => '2026-08-26',
        'start_time' => '12:00',
        'end_time' => '17:00',
        'break_minutes' => 0,
    ]);

    expect($entry->exists)->toBeTrue();
});

it('blokkeert een registratie die volledig binnen een andere valt', function () {
    $user = User::factory()->create();

    TimeEntry::factory()->for($user)->create([
        'date' => '2026-08-26',
        'start_time' => '09:00',
        'end_time' => '12:00',
        'break_minutes' => 0,
    ]);

    expect(fn () => TimeEntry::factory()->for($user)->create([
        'date' => '2026-08-26',
        'start_time' => '10:00',
        'end_time' => '11:00',
        'break_minutes' => 0,
    ]))->toThrow(ValidationException::class);
});

it('berekent en slaat de duur op in duration_minutes', function () {
    $user = User::factory()->create();

    $entry = TimeEntry::factory()->for($user)->create([
        'date' => '2026-08-26',
        'start_time' => '09:00',
        'end_time' => '17:00',
        'break_minutes' => 30,
    ]);

    expect($entry->fresh()->duration_minutes)->toBe(450);
    expect($user->fresh()->totalLoggedMinutes())->toBe(450);
});

it('weigert een eindtijd die vóór de begintijd ligt', function () {
    $user = User::factory()->create();

    expect(fn () => TimeEntry::factory()->for($user)->create([
        'date' => '2026-08-26',
        'start_time' => '17:00',
        'end_time' => '09:00',
        'break_minutes' => 0,
    ]))->toThrow(ValidationException::class);
});
