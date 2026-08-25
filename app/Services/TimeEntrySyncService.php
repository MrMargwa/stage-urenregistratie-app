<?php

namespace App\Services;

use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;

class TimeEntrySyncService
{
    /**
     * Header-aliassen die we herkennen (genormaliseerd naar kleine letters zonder spaties).
     */
    private const HEADER_ALIASES = [
        'date' => ['datum', 'date', 'dag', 'werkdag'],
        'start_time' => ['begintijd', 'begin', 'starttijd', 'start', 'van', 'vanaf'],
        'end_time' => ['eindtijd', 'eind', 'einde', 'end', 'tot', 'totmet', 'tm'],
        'break_minutes' => ['pauze', 'pauzeminuten', 'pauzemin', 'break', 'breakminutes', 'breakminutes', 'pauzeduur'],
        'description' => ['beschrijving', 'omschrijving', 'description', 'werkzaamheden', 'activiteit', 'notities', 'opmerking', 'opmerkingen'],
    ];

    /**
     * @param  string  $absolutePath  Pad naar het geüploade .xlsx- of .csv-bestand
     * @param  bool  $deleteMissing  Verwijder registraties van deze gebruiker die niet in het bestand staan
     */
    public function syncFromFile(User $user, string $absolutePath, bool $deleteMissing = false): SyncResult
    {
        $rows = $this->readRows($absolutePath);

        [$map, $headerRow] = $this->detectColumnMap($rows);

        if ($map === null) {
            throw new RuntimeException(
                'Kon geen geldige kolommen vinden. Het bestand moet een kopregel bevatten met minimaal: datum, begintijd en eindtijd.'
            );
        }

        $result = new SyncResult;

        WorkbookService::withoutAutoRefresh(function () use ($user, $rows, $map, $headerRow, $deleteMissing, $result): void {
            DB::transaction(function () use ($user, $rows, $map, $headerRow, $deleteMissing, $result): void {
                $touchedIds = [];

                $existingEntries = $user->timeEntries()->get()->keyBy(function (TimeEntry $entry): string {
                    return $entry->date->toDateString().'|'.$entry->start_time->format('H:i');
                });

                foreach ($rows as $index => $cells) {
                    if ($index <= $headerRow) {
                        continue;
                    }

                    $attributes = $this->extractAttributes($cells, $map);

                    if ($attributes === null) {
                        continue;
                    }

                    $rowNumber = $index + 1;

                    if (($errors = $this->validateAttributes($attributes)) !== []) {
                        $result->errors[] = "Rij {$rowNumber}: ".implode(' ', $errors);
                        $result->skipped++;

                        continue;
                    }

                    $matchKey = $attributes['date'].'|'.$attributes['start_time'];

                    if (($existing = $existingEntries->get($matchKey)) !== null) {
                        $existing->update([
                            'end_time' => $attributes['end_time'],
                            'break_minutes' => $attributes['break_minutes'],
                            'description' => $attributes['description'],
                        ]);

                        $touchedIds[] = $existing->id;
                        $result->updated++;

                        continue;
                    }

                    $created = TimeEntry::query()->create([
                        ...$attributes,
                        'user_id' => $user->id,
                    ]);

                    $existingEntries->put($matchKey, $created);

                    $touchedIds[] = $created->id;
                    $result->created++;
                }

                if ($deleteMissing) {
                    // Mass-delete vuurt geen model events af; het werkblad wordt
                    // hieronder na de transactie één keer ververst.
                    $result->deleted = TimeEntry::query()
                        ->where('user_id', $user->id)
                        ->whereNotIn('id', $touchedIds)
                        ->delete();
                }
            });
        });

        app(WorkbookService::class)->refresh($user);

        return $result;
    }

    /**
     * Leest alle rijen als platte arrays met cellen.
     *
     * @return array<int, array<int, mixed>>
     */
    private function readRows(string $path): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $reader = match ($extension) {
            'csv' => new CsvReader,
            'xlsx', 'xls' => new XlsxReader,
            default => throw new RuntimeException('Ongeldig bestandstype. Upload een .xlsx- of .csv-bestand.'),
        };

        $rows = [];

        $reader->open($path);

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->toArray();

                if ($cells === [] || $this->isBlankRow($cells)) {
                    continue;
                }

                $rows[] = $cells;
            }
        }

        $reader->close();

        return $rows;
    }

    /**
     * Zoekt de kolomposities van de benodigde velden in de kopregel.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{array<string, int>|null, int}
     */
    private function detectColumnMap(array $rows): array
    {
        foreach ($rows as $index => $cells) {
            $normalized = [];

            foreach ($cells as $position => $value) {
                $normalized[$position] = $this->normalizeHeader((string) $value);
            }

            $map = [];

            foreach (self::HEADER_ALIASES as $field => $aliases) {
                foreach ($normalized as $position => $header) {
                    if (in_array($header, $aliases, true)) {
                        $map[$field] = $position;

                        break;
                    }
                }
            }

            if (isset($map['date'], $map['start_time'], $map['end_time'])) {
                return [$map, $index];
            }
        }

        return [null, -1];
    }

    /**
     * Zet een rij om naar database-attributen. Geeft null terug bij een lege rij.
     *
     * @param  array<int, mixed>  $cells
     * @param  array<string, int>  $map
     * @return array<string, mixed>|null
     */
    private function extractAttributes(array $cells, array $map): ?array
    {
        $get = fn (string $field): mixed => isset($map[$field]) ? ($cells[$map[$field]] ?? null) : null;

        if (filled($get('description')) === false && filled($get('date')) === false) {
            return null;
        }

        $date = $this->parseDate($get('date'));
        $start = $this->parseTime($get('start_time'));
        $end = $this->parseTime($get('end_time'));

        if ($date === null || $start === null || $end === null) {
            return [
                '__invalid' => true,
                'date' => $get('date'),
                'start_time' => $get('start_time'),
                'end_time' => $get('end_time'),
            ];
        }

        return [
            'date' => $date->toDateString(),
            'start_time' => $start,
            'end_time' => $end,
            'break_minutes' => $this->parseBreakMinutes($get('break_minutes')),
            'description' => filled($get('description')) ? trim((string) $get('description')) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string>
     */
    private function validateAttributes(array $attributes): array
    {
        if (! empty($attributes['__invalid'])) {
            return [sprintf(
                "onleesbare waarden (datum: '%s', begintijd: '%s', eindtijd: '%s').",
                $attributes['date'] ?? '',
                $attributes['start_time'] ?? '',
                $attributes['end_time'] ?? ''
            )];
        }

        return [];
    }

    private function parseDate(mixed $value): ?CarbonInterface
    {
        $value = $this->stringify($value);

        if (blank($value)) {
            return null;
        }

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'd-m-y', 'd/m/y'] as $format) {
            $date = Carbon::hasFormat($value, $format) ? Carbon::createFromFormat($format, $value) : null;

            if ($date !== null) {
                return $date->startOfDay();
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseTime(mixed $value): ?string
    {
        if (is_float($value) || is_int($value)) {
            $fraction = (float) $value;

            if ($fraction >= 0.0 && $fraction < 1.0) {
                $minutes = (int) round($fraction * 1440);

                return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
            }
        }

        $raw = $this->stringify($value);

        if (blank($raw)) {
            return null;
        }

        $normalized = str_replace(['.', ','], ':', trim($raw));

        if (preg_match('/^(\d{1,2}):(\d{1,2})(:\d{1,2})?$/', $normalized, $matches)) {
            return sprintf('%02d:%02d', min(23, (int) $matches[1]), min(59, (int) $matches[2]));
        }

        if (preg_match('/^(\d{1,2})$/', $normalized, $matches)) {
            return sprintf('%02d:00', min(23, (int) $matches[1]));
        }

        return null;
    }

    private function parseBreakMinutes(mixed $value): int
    {
        $raw = $this->stringify($value);

        if (blank($raw)) {
            return 0;
        }

        if (! is_numeric(str_replace(',', '.', $raw))) {
            return 0;
        }

        return max(0, (int) round((float) str_replace(',', '.', $raw)));
    }

    private function stringify(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return trim((string) $value);
    }

    private function normalizeHeader(string $value): string
    {
        $value = mb_strtolower(trim($value));

        $replacements = [
            '/[\s\-_:.()]+/' => '',
            '/ë|é|è/' => 'e',
            '/ï|í|ì/' => 'i',
            '/á|à|â/' => 'a',
            '/ó|ò|ô/' => 'o',
            '/ú|ù|û/' => 'u',
        ];

        return (string) preg_replace(array_keys($replacements), array_values($replacements), $value);
    }

    /**
     * @param  array<int, mixed>  $cells
     */
    private function isBlankRow(array $cells): bool
    {
        return collect($cells)->every(fn ($cell) => blank($cell));
    }
}
