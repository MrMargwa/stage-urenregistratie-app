<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\UsersSeeder;
use Illuminate\Support\Facades\Hash;

it('seedt precies één admin-account met het standaard wachtwoord', function () {
    $this->seed(UsersSeeder::class);

    $admin = User::where('email', UsersSeeder::ADMIN_EMAIL)->first();

    expect($admin)->not->toBeNull()
        ->and($admin->name)->toBe('Admin')
        ->and($admin->role)->toBe(Role::Admin)
        ->and(Hash::check(UsersSeeder::ADMIN_DEFAULT_PASSWORD, $admin->password))->toBeTrue();
});

it('is idempotent en herstelt een gewijzigd admin-wachtwoord niet', function () {
    $this->seed(UsersSeeder::class);

    $admin = User::where('email', UsersSeeder::ADMIN_EMAIL)->firstOrFail();
    $admin->update(['password' => 'NieuwWachtwoord123!']);

    $this->seed(UsersSeeder::class);

    $admin = $admin->fresh();

    expect(User::where('email', UsersSeeder::ADMIN_EMAIL)->count())->toBe(1)
        ->and(Hash::check(UsersSeeder::ADMIN_DEFAULT_PASSWORD, $admin->password))->toBeFalse()
        ->and(Hash::check('NieuwWachtwoord123!', $admin->password))->toBeTrue();
});
