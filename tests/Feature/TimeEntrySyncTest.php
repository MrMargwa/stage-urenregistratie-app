<?php

use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeEntrySyncService;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

function schrijfXlsx(string $pad, array $rijen): void
{
    $writer = new Writer;
    $writer->openToFile($pad);

    foreach ($rijen as $rij) {
        $writer->addRow(Row::fromValues($rij));
    }

    $writer->close();
}

it('maakt nieuwe registraties aan vanuit een Excel-bestand met Nederlandse kopregels', function () {
    $user = User::factory()->create();
    $pad = sys_get_temp_dir().'/sync-maak-aan.xlsx';

    schrijfXlsx($pad, [
        ['Datum', 'Begintijd', 'Eindtijd', 'Pauze (minuten)', 'Beschrijving'],
        ['26-08-2026', '09:00', '17:00', '30', 'Aan de app gewerkt'],
        ['27-08-2026', '9', '16.5', '0', ''],
    ]);

    $result = app(TimeEntrySyncService::class)->syncFromFile($user, $pad);

    expect($result->created)->toBe(2)
        ->and($result->updated)->toBe(0)
        ->and($result->errors)->toBe([]);

    $eerste = $user->timeEntries()->where('description', 'Aan de app gewerkt')->firstOrFail();

    expect($eerste->date->toDateString())->toBe('2026-08-26')
        ->and($eerste->start_time->format('H:i'))->toBe('09:00')
        ->and($eerste->end_time->format('H:i'))->toBe('17:00')
        ->and($eerste->break_minutes)->toBe(30);

    $tweede = $user->timeEntries()->whereDate('date', '2026-08-27')->firstOrFail();

    expect($tweede->start_time->format('H:i'))->toBe('09:00')
        ->and($tweede->end_time->format('H:i'))->toBe('16:05');

    unlink($pad);
});

it('werkt ook met Engelse kopregels en ISO-datums', function () {
    $user = User::factory()->create();
    $pad = sys_get_temp_dir().'/sync-engels.xlsx';

    schrijfXlsx($pad, [
        ['Date', 'Start', 'End', 'Break', 'Description'],
        ['2026-08-28', '10:15', '18:45', '45', 'English works too'],
    ]);

    $result = app(TimeEntrySyncService::class)->syncFromFile($user, $pad);

    expect($result->created)->toBe(1)
        ->and($result->errors)->toBe([]);

    $entry = $user->timeEntries()->firstOrFail();

    expect($entry->date->toDateString())->toBe('2026-08-28')
        ->and($entry->start_time->format('H:i'))->toBe('10:15')
        ->and($entry->end_time->format('H:i'))->toBe('18:45')
        ->and($entry->break_minutes)->toBe(45);

    unlink($pad);
});

it('werkt bestaande registraties bij op basis van datum en begintijd', function () {
    $user = User::factory()->create();

    TimeEntry::factory()->for($user)->create([
        'date' => '2026-08-26',
        'start_time' => '09:00',
        'end_time' => '17:00',
        'break_minutes' => 30,
        'description' => 'Oude tekst',
    ]);

    $pad = sys_get_temp_dir().'/sync-bijwerken.xlsx';

    schrijfXlsx($pad, [
        ['Datum', 'Begin', 'Eind', 'Pauze', 'Omschrijving'],
        ['26-08-2026', '09:00', '20:00', '60', 'Nieuwe tekst'],
    ]);

    $result = app(TimeEntrySyncService::class)->syncFromFile($user, $pad);

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(1);

    $entry = $user->timeEntries()->firstOrFail();

    expect($entry->end_time->format('H:i'))->toBe('20:00')
        ->and($entry->break_minutes)->toBe(60)
        ->and($entry->description)->toBe('Nieuwe tekst')
        ->and($user->timeEntries()->count())->toBe(1);

    unlink($pad);
});

it('verwijdert registraties die niet in het bestand staan als dat is aangevinkt', function () {
    $user = User::factory()->create();

    TimeEntry::factory()->for($user)->create(['date' => '2026-01-05']);
    TimeEntry::factory()->for($user)->create(['date' => '2026-01-06']);

    $ander = User::factory()->create();
    $vreemdeEntry = TimeEntry::factory()->for($ander)->create(['date' => '2026-02-01']);

    $pad = sys_get_temp_dir().'/sync-verwijderen.xlsx';

    schrijfXlsx($pad, [
        ['Datum', 'Begin', 'Eind'],
        ['05-01-2026', '09:00', '17:00'],
    ]);

    $result = app(TimeEntrySyncService::class)->syncFromFile($user, $pad, deleteMissing: true);

    expect($result->deleted)->toBe(1);

    expect($user->timeEntries()->count())->toBe(1)
        ->and($user->timeEntries()->first()->date->format('Y-m-d'))->toBe('2026-01-05')
        ->and(TimeEntry::find($vreemdeEntry->id))->not->toBeNull();

    unlink($pad);
});

it('behoudt registraties zonder verwijderoptie', function () {
    $user = User::factory()->create();
    TimeEntry::factory()->for($user)->create(['date' => '2026-01-05']);

    $pad = sys_get_temp_dir().'/sync-behouden.xlsx';

    schrijfXlsx($pad, [
        ['Datum', 'Begin', 'Eind'],
        ['10-03-2026', '08:00', '12:00'],
    ]);

    $result = app(TimeEntrySyncService::class)->syncFromFile($user, $pad);

    expect($result->deleted)->toBe(0)
        ->and($user->timeEntries()->count())->toBe(2);

    unlink($pad);
});

it('slaat ongeldige rijen over en rapporteert ze', function () {
    $user = User::factory()->create();
    $pad = sys_get_temp_dir().'/sync-fouten.xlsx';

    schrijfXlsx($pad, [
        ['Datum', 'Begin', 'Eind', 'Pauze', 'Omschrijving'],
        ['geen datum', 'nul uur', 'ook niet', '', 'kapotte rij'],
        ['01-04-2026', '09:00', '17:00', '', 'goede rij'],
    ]);

    $result = app(TimeEntrySyncService::class)->syncFromFile($user, $pad);

    expect($result->created)->toBe(1)
        ->and($result->skipped)->toBe(1)
        ->and($result->errors)->toHaveCount(1)
        ->and($result->errors[0])->toContain('Rij 2');

    expect(TimeEntry::where('description', 'goede rij')->exists())->toBeTrue();

    unlink($pad);
});

it('gooit een duidelijke fout bij ontbrekende kolommen', function () {
    $user = User::factory()->create();
    $pad = sys_get_temp_dir().'/-sync-kolommen.xlsx';

    schrijfXlsx($pad, [
        ['Foo', 'Bar'],
        ['1', '2'],
    ]);

    app(TimeEntrySyncService::class)->syncFromFile($user, $pad);
})->throws(RuntimeException::class, 'kopregel');

it('berekent de duur correct over middernacht', function () {
    $entry = TimeEntry::factory()->make([
        'start_time' => '22:00',
        'end_time' => '06:00',
        'break_minutes' => 0,
    ]);

    expect($entry->duration)->toBe(480);
});
