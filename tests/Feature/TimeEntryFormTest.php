<?php

use App\Filament\Admin\Resources\TimeEntries\Pages\CreateTimeEntry;
use App\Models\TimeEntry;
use App\Models\User;
use Livewire\Livewire;

it('rendert het aanmaakformulier met de afwezigheidsschakelaar', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateTimeEntry::class)
        ->assertSuccessful()
        ->assertFormFieldExists('is_absent')
        ->assertFormFieldExists('start_time');
});

it('verbergt de tijdvelden zodra de dag als afwezig is gemarkeerd', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateTimeEntry::class)
        ->fillForm(['is_absent' => true])
        ->assertFormFieldHidden('start_time')
        ->assertFormFieldHidden('end_time')
        ->assertFormFieldHidden('break_minutes');
});

it('slaat een afwezigheidsdag op zonder tijden', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateTimeEntry::class)
        ->fillForm([
            'date' => '2026-09-09',
            'is_absent' => true,
            'description' => 'Vakantie',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $entry = TimeEntry::query()->sole();

    expect($entry->isAbsent())->toBeTrue()
        ->and($entry->date->format('Y-m-d'))->toBe('2026-09-09')
        ->and($entry->duration_minutes)->toBe(0)
        ->and($entry->description)->toBe('Vakantie');
});

it('vult nieuwe registraties voor met de standaardtijden van de gebruiker', function () {
    $user = User::factory()->create([
        'default_start_time' => '07:00',
        'default_end_time' => '15:30',
        'default_break_minutes' => 45,
    ]);

    Livewire::actingAs($user)
        ->test(CreateTimeEntry::class)
        ->fillForm(['date' => '2026-09-09'])
        ->call('create')
        ->assertHasNoFormErrors();

    $entry = TimeEntry::query()->sole();

    expect($entry->start_time->format('H:i'))->toBe('07:00')
        ->and($entry->end_time->format('H:i'))->toBe('15:30')
        ->and($entry->break_minutes)->toBe(45)
        ->and($entry->duration_minutes)->toBe(465);
});

it('valt terug op 08:30 – 17:00 met 30 minuten pauze zonder eigen instellingen', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateTimeEntry::class)
        ->fillForm(['date' => '2026-09-09'])
        ->call('create')
        ->assertHasNoFormErrors();

    $entry = TimeEntry::query()->sole();

    expect($entry->start_time->format('H:i'))->toBe('08:30')
        ->and($entry->end_time->format('H:i'))->toBe('17:00')
        ->and($entry->break_minutes)->toBe(30)
        ->and($entry->duration_minutes)->toBe(480);
});

it('vult de datum standaard met vandaag', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateTimeEntry::class)
        ->fillForm([
            'start_time' => '09:00',
            'end_time' => '17:00',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(TimeEntry::query()->sole()->date->toDateString())->toBe(today()->toDateString());
});
