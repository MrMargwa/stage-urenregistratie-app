<?php

use App\Models\TimeEntry;
use App\Models\User;

it('verwijdert de stage-uren van een gebruiker wanneer die gebruiker wordt verwijderd', function () {
    $user = User::factory()->create();

    $times = [
        ['09:00', '10:00'],
        ['10:00', '11:00'],
        ['11:00', '12:00'],
    ];

    foreach ($times as [$start, $end]) {
        TimeEntry::factory()->for($user)->create([
            'date' => '2026-08-26',
            'start_time' => $start,
            'end_time' => $end,
            'break_minutes' => 0,
        ]);
    }

    expect(TimeEntry::where('user_id', $user->id)->count())->toBe(3);

    $entryIds = TimeEntry::where('user_id', $user->id)->pluck('id');

    $user->delete();

    expect(TimeEntry::find($entryIds->all()))->each->toBeNull();
});

it('beperkt de ownedBy-scope tot eigen registraties', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $own = TimeEntry::factory()->for($owner)->create([
        'date' => '2026-08-26',
        'start_time' => '09:00',
        'end_time' => '10:00',
        'break_minutes' => 0,
    ]);
    $others = TimeEntry::factory()->for($other)->create([
        'date' => '2026-08-26',
        'start_time' => '11:00',
        'end_time' => '12:00',
        'break_minutes' => 0,
    ]);

    $ownedIds = TimeEntry::ownedBy($owner)->pluck('id');

    expect($ownedIds)->toContain($own->id)
        ->and($ownedIds)->not->toContain($others->id);
});
