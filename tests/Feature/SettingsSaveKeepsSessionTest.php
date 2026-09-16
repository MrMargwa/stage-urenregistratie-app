<?php

use App\Filament\Admin\Pages\Settings;
use App\Models\User;
use Livewire\Livewire;

it('laat de gebruiker ingelogd als hij alleen het stage-uren doel aanpast', function () {
    $user = User::factory()->create(['target_hours' => null]);

    Livewire::actingAs($user)
        ->test(Settings::class)
        ->fillForm([
            'name' => $user->name,
            'email' => $user->email,
            'target_hours' => 500,
        ])
        ->call('save');

    expect(auth()->check())->toBeTrue()
        ->and(auth()->user()->id)->toBe($user->id)
        ->and($user->fresh()->target_hours)->toBe(500);
});

it('laat de gebruiker ingelogd als hij zijn wachtwoord wijzigt', function () {
    $user = User::factory()->create();
    $oldHash = $user->password;

    Livewire::actingAs($user)
        ->test(Settings::class)
        ->fillForm([
            'name' => $user->name,
            'email' => $user->email,
            'target_hours' => 500,
            'password' => 'NieuwWachtwoord99',
        ])
        ->call('save');

    $fresh = $user->fresh();

    expect($fresh->password)->not->toBe($oldHash)
        ->and(auth()->check())->toBeTrue()
        ->and(auth()->user()->id)->toBe($user->id);
});
