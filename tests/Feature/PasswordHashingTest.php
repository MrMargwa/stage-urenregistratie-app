<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('hashes een wachtwoord exact één keer bij opslag via het model', function () {
    $user = User::factory()->create();

    $user->update(['password' => 'NieuwWacht123']);

    $fresh = $user->fresh();

    expect(Hash::check('NieuwWacht123', $fresh->password))->toBeTrue();

    $storedParts = explode('$', $fresh->password);
    expect($storedParts[0])->toBe('');
});

it('behoudt een succesvolle login na het wijzigen van het wachtwoord in settings', function () {
    $user = User::factory()->create(['password' => 'OudWacht123']);

    $user->update(['password' => 'NieuwWacht123']);

    expect(Hash::check('OudWacht123', $user->fresh()->password))->toBeFalse()
        ->and(Hash::check('NieuwWacht123', $user->fresh()->password))->toBeTrue();
});
