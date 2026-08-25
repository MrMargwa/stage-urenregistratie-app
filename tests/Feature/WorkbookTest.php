<?php

use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeEntrySyncService;
use App\Services\WorkbookService;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;

use function Pest\Laravel\actingAs;

function leesWerkblad(string $pad): array
{
    $reader = new Reader;
    $reader->open($pad);

    $rijen = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rijen[] = $row->toArray();
        }

        break;
    }

    $reader->close();

    return $rijen;
}

it('genereert bij het koppelen een werkblad met alle uren en een totaalregel', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    TimeEntry::factory()->for($user)->create([
        'date' => '2026-08-24',
        'start_time' => '09:00',
        'end_time' => '17:00',
        'break_minutes' => 30,
        'description' => 'Werken aan features',
    ]);

    $workbooks = app(WorkbookService::class);
    $workbooks->link($user);

    expect($user->refresh()->hasLinkedWorkbook())->toBeTrue()
        ->and($workbooks->exists($user))->toBeTrue();

    $rijen = leesWerkblad($workbooks->absolutePath($user));

    expect($rijen)->toHaveCount(3)
        ->and($rijen[0])->toEqual(['Datum', 'Begintijd', 'Eindtijd', 'Pauze (min)', 'Duur', 'Beschrijving'])
        ->and($rijen[1][5])->toBe('Werken aan features')
        ->and($rijen[1][4])->toBe('07:30')
        ->and($rijen[2][5])->toBe('Totaal');
});

it('werkt het gekoppelde werkblad automatisch bij bij een nieuw uur', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $workbooks = app(WorkbookService::class);
    $workbooks->link($user);

    expect(count(leesWerkblad($workbooks->absolutePath($user))))->toBe(2);

    TimeEntry::factory()->for($user)->create([
        'date' => '2026-08-25',
        'description' => 'Nieuw uur',
    ]);

    $rijen = leesWerkblad($workbooks->absolutePath($user));

    expect($rijen)->toHaveCount(3)
        ->and($rijen[1][5])->toBe('Nieuw uur');
});

it('stopt met bijwerken na het ontkoppelen', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $workbooks = app(WorkbookService::class);
    $workbooks->link($user);

    expect($workbooks->exists($user))->toBeTrue();

    $workbooks->unlink($user);

    expect($user->refresh()->hasLinkedWorkbook())->toBeFalse()
        ->and($workbooks->exists($user))->toBeFalse();

    TimeEntry::factory()->for($user)->create();

    expect($workbooks->exists($user))->toBeFalse();
});

it('laat gekoppelde gebruikers hun werkblad downloaden', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    app(WorkbookService::class)->link($user);

    actingAs($user)
        ->get(route('workbook.download'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('stuurt gebruikers zonder gekoppeld werkblad door in plaats van een 404 te tonen', function () {
    actingAs(User::factory()->create())
        ->get(route('workbook.download'))
        ->assertRedirect('/admin');
});

it('ververst het werkblad na een Excel-sync', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $workbooks = app(WorkbookService::class);
    $workbooks->link($user);

    $pad = sys_get_temp_dir().'/sync-werkblad.xlsx';
    $writer = new Writer;
    $writer->openToFile($pad);
    $writer->addRow(Row::fromValues(['Datum', 'Begin', 'Eind']));
    $writer->addRow(Row::fromValues(['26-08-2026', '09:00', '17:00']));
    $writer->close();

    app(TimeEntrySyncService::class)->syncFromFile($user, $pad);
    unlink($pad);

    $rijen = leesWerkblad($workbooks->absolutePath($user));

    expect($rijen)->toHaveCount(3)
        ->and($rijen[1][0])->toBe('26-08-2026')
        ->and($rijen[2][5])->toBe('Totaal');
});
