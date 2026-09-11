<?php

use App\Filament\Admin\Pages\Settings;
use App\Models\User;
use Livewire\Livewire;

it('slaat de standaard werktijden op', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Settings::class)
        ->fillForm([
            'default_start_time' => '07:15',
            'default_end_time' => '16:45',
            'default_break_minutes' => 20,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $fresh = $user->fresh();

    expect($fresh->defaultStartTime())->toBe('07:15')
        ->and($fresh->defaultEndTime())->toBe('16:45')
        ->and($fresh->defaultBreakMinutes())->toBe(20);
});

it('valt zonder eigen instellingen terug op de app-standaarden', function () {
    $user = User::factory()->create([
        'default_start_time' => null,
        'default_end_time' => null,
        'default_break_minutes' => null,
    ]);

    expect($user->defaultStartTime())->toBe('08:30')
        ->and($user->defaultEndTime())->toBe('17:00')
        ->and($user->defaultBreakMinutes())->toBe(30);
});
