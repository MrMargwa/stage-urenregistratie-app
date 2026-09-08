<?php

use App\Enums\Role;
use App\Models\TimeEntry;
use App\Models\User;
use Database\Seeders\UsersSeeder;

it('maakt bij het seeden een admin en een testaccount met verschillende e-mails aan', function () {
    config()->set('seeding.admin_email', 'admin@example.com');
    config()->set('seeding.user_email', 'testaccount01@example.com');
    config()->set('seeding.admin_password', 'secret-admin-pass');
    config()->set('seeding.user_password', 'secret-user-pass');

    $this->seed(UsersSeeder::class);

    $admin = User::where('email', 'admin@example.com')->first();
    $testUser = User::where('email', 'testaccount01@example.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->role)->toBe(Role::Admin)
        ->and($testUser)->not->toBeNull()
        ->and($testUser->role)->toBe(Role::User)
        ->and($testUser->id)->not->toBe($admin->id);
});

it('is idempotent bij herhaald seeden en wijzigt bestaande stages niet', function () {
    config()->set('seeding.admin_email', 'admin@example.com');
    config()->set('seeding.admin_password', 'secret-admin-pass');

    $this->seed(UsersSeeder::class);

    $admin = User::where('email', 'admin@example.com')->firstOrFail();
    $entry = TimeEntry::factory()->for($admin)->create([
        'date' => '2026-08-26',
        'start_time' => '09:00',
        'end_time' => '12:00',
    ]);

    $this->seed(UsersSeeder::class);

    expect(User::where('email', 'admin@example.com')->count())->toBe(1)
        ->and($admin->fresh()->timeEntries()->count())->toBe(1)
        ->and($admin->fresh()->timeEntries()->first()->id)->toBe($entry->id);
});

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

    $own = TimeEntry::factory()->for($owner)->create(['date' => '2026-08-26', 'start_time' => '09:00', 'end_time' => '10:00']);
    $others = TimeEntry::factory()->for($other)->create(['date' => '2026-08-26', 'start_time' => '11:00', 'end_time' => '12:00']);

    $ownedIds = TimeEntry::ownedBy($owner)->pluck('id');

    expect($ownedIds)->toContain($own->id)
        ->and($ownedIds)->not->toContain($others->id);
});
