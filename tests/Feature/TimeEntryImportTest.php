<?php

use App\Filament\Admin\Resources\TimeEntries\Pages\ListTimeEntries;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeEntryImporter;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Maakt een tijdelijk .xlsx-bestand met dezelfde opzet als de export.
 *
 * @param  array<int, array<int, mixed>>  $rows
 */
function makeImportFile(array $rows): string
{
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'time-entry-import-'.uniqid().'.xlsx';

    $writer = new Writer;
    $writer->openToFile($path);
    $writer->addRow(Row::fromValues(['Week', 'Datum', 'Begintijd', 'Eindtijd', 'Pauze (minuten)', 'Beschrijving', 'Duur']));

    foreach ($rows as $row) {
        $writer->addRow(Row::fromValues($row));
    }

    $writer->close();

    return $path;
}

it('importeert uren en beschrijvingen uit een exportbestand', function () {
    $user = User::factory()->create();

    $path = makeImportFile([
        ['Week 36', '09-09-2026', '09:00', '17:00', 30, 'Stage gelopen', '07:30'],
    ]);

    $result = app(TimeEntryImporter::class)->import($path, $user);

    @unlink($path);

    expect($result)->toBe(['imported' => 1, 'skipped' => 0]);

    $entry = TimeEntry::query()->sole();

    expect($entry->user_id)->toBe($user->id)
        ->and($entry->date->format('Y-m-d'))->toBe('2026-09-09')
        ->and($entry->start_time->format('H:i'))->toBe('09:00')
        ->and($entry->end_time->format('H:i'))->toBe('17:00')
        ->and($entry->break_minutes)->toBe(30)
        ->and($entry->duration_minutes)->toBe(450)
        ->and($entry->description)->toBe('Stage gelopen')
        ->and($entry->isAbsent())->toBeFalse();
});

it('importeert een afwezigheidsdag zonder tijden', function () {
    $user = User::factory()->create();

    $path = makeImportFile([
        ['Week 36', '10-09-2026', '', '', '', 'Ziek', '00:00'],
    ]);

    app(TimeEntryImporter::class)->import($path, $user);

    @unlink($path);

    $entry = TimeEntry::query()->sole();

    expect($entry->isAbsent())->toBeTrue()
        ->and($entry->start_time)->toBeNull()
        ->and($entry->end_time)->toBeNull()
        ->and($entry->duration_minutes)->toBe(0)
        ->and($entry->description)->toBe('Ziek');
});

it('slaat rijen zonder geldige datum over', function () {
    $user = User::factory()->create();

    $path = makeImportFile([
        ['Week 36', 'geen datum', '09:00', '17:00', 0, 'Ongeldig', ''],
    ]);

    $result = app(TimeEntryImporter::class)->import($path, $user);

    @unlink($path);

    expect($result)->toBe(['imported' => 0, 'skipped' => 1])
        ->and(TimeEntry::query()->count())->toBe(0);
});

it('rendert de tijdregistraties-pagina met de importactie', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ListTimeEntries::class)
        ->assertSuccessful()
        ->assertActionExists('import');
});

it('verbergt de importactie zodra de gebruiker al uren heeft', function () {
    $user = User::factory()->create();
    TimeEntry::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(ListTimeEntries::class)
        ->assertActionHidden('import');
});
