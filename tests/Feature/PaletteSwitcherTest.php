<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

it('toont de filament-palette kleurensswitcher in het gebruikersmenu', function () {
    $html = actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('fi-theme-switcher')
        ->toContain('setColor(')
        ->toContain('ui-switcher-modal');
});
