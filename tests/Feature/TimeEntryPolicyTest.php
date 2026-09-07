<?php

use App\Models\TimeEntry;
use App\Models\User;

it('laat een gebruiker alleen zijn eigen tijdregistraties beheren', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $owned = TimeEntry::factory()->for($owner)->create();
    $others = TimeEntry::factory()->for($other)->create([
        'date' => '2026-08-26',
    ]);

    expect($owner->can('view', $owned))->toBeTrue()
        ->and($owner->can('update', $owned))->toBeTrue()
        ->and($owner->can('delete', $owned))->toBeTrue()
        ->and($owner->can('view', $others))->toBeFalse()
        ->and($owner->can('update', $others))->toBeFalse()
        ->and($owner->can('delete', $others))->toBeFalse();
});

it('staat eenmalige bulkverwijdering van tijdregistraties niet toe', function () {
    $user = User::factory()->create();

    expect($user->can('deleteAny', TimeEntry::class))->toBeFalse();
});

it('bereidt user_id voor op de nieuwe registratie en wijzigt deze nooit op opslag', function () {
    $user = User::factory()->create();

    $entry = TimeEntry::factory()->for($user)->create([
        'date' => '2026-08-26',
        'start_time' => '09:00',
        'end_time' => '12:00',
        'break_minutes' => 0,
    ]);

    $entry->update(['description' => 'gewijzigd']);

    expect($entry->fresh()->user_id)->toBe($user->id);
});
