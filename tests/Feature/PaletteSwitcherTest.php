<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

it('toont de filament-palette kleurensswitcher in het gebruikersmenu', function () {
    $html = actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->getContent();

    $checks = [
        'fi-theme-switcher' => str_contains($html, 'fi-theme-switcher'),
        'wire:click=apply' => str_contains($html, 'wire:click=&quot;apply(') || str_contains($html, 'wire:click="apply('),
        'palette kleuren (amber/teal/slate)' => str_contains($html, 'AMBER') || str_contains($html, 'TEAL') || str_contains($html, 'SLATE'),
    ];

    file_put_contents(sys_get_temp_dir().'/palette_checks.txt', json_encode($checks, JSON_PRETTY_PRINT));

    expect($checks['fi-theme-switcher'])->toBeTrue()
        ->and($checks['wire:click=apply'])->toBeTrue();
});
