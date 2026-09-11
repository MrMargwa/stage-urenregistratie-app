<?php

use App\Enums\Role;
use App\Models\User;

it('laat een admin een andere admin verwijderen zolang niet de laatste', function () {
    $adminA = User::factory()->admin()->create();
    $adminB = User::factory()->admin()->create();

    expect($adminA->can('delete', $adminB))->toBeTrue();
});

it('laat een admin een gewone gebruiker verwijderen', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['role' => Role::Student]);

    expect($admin->can('delete', $user))->toBeTrue();
});

it('blokkeert het verwijderen van zichzelf als laatste admin', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('delete', $admin))->toBeFalse();
});

it('blokkeert het verwijderen van een andere admin die de laatste is', function () {
    $admin = User::factory()->admin()->create();
    $lastAdmin = User::factory()->admin()->create();

    $admin->delete();

    expect($lastAdmin->fresh()->isLastAdmin())->toBeTrue()
        ->and($admin->can('delete', $lastAdmin))->toBeFalse();
});

it('markeert een admin als laatste als er geen andere admins zijn', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->isLastAdmin())->toBeTrue();

    User::factory()->admin()->create();

    expect($admin->fresh()->isLastAdmin())->toBeFalse();
});
