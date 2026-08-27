<?php

use App\Models\TimeEntry;
use App\Models\User;
use App\Services\ExportService;

it('exporteert alle uren als een XLSX zonder fouten', function () {
    $user = User::factory()->create([
        'accent_color' => 'blue',
    ]);

    TimeEntry::factory()->for($user)->create([
        'date' => '2026-08-26',
        'start_time' => '09:00',
        'end_time' => '17:00',
        'break_minutes' => 30,
        'description' => 'Stage-uren',
    ]);

    $entries = app(ExportService::class)->getAllEntries($user);

    $response = app(ExportService::class)->exportToXlsx($entries, 'test.xlsx', $user->exportColors());

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('Content-Type'))->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});
