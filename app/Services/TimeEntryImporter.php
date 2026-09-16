<?php

namespace App\Services;

use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Validation\ValidationException;
use OpenSpout\Reader\XLSX\Reader;

/**
 * Leest een geëxporteerd .xlsx-bestand terug in en maakt er tijdregistraties
 * van voor één gebruiker. Bedoeld als restore: het bestand komt uit de
 * eigen "Exporteren (.xlsx)"-knop.
 *
 * Kolommen worden op hun kopregel herkend (Datum, Begintijd, Eindtijd,
 * Pauze, Beschrijving), niet op vaste positie. Zo blijft de import werken
 * als er ooit een kolom bijkomt of van plek wisselt.
 */
class TimeEntryImporter
{
    /**
     * @return array{imported: int, skipped: int}
     */
    public function import(string $path, User $user): array
    {
        $reader = new Reader;
        $reader->open($path);

        $imported = 0;
        $skipped = 0;
        $columns = null;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $values = array_map(fn ($cell) => $cell->getValue(), $row->getCells());

                if ($columns === null) {
                    $columns = $this->detectColumns($values);

                    if ($columns === []) {
                        continue;
                    }

                    continue;
                }

                if ($this->isEmptyRow($values)) {
                    continue;
                }

                if ($this->createEntry($values, $columns, $user)) {
                    $imported++;
                } else {
                    $skipped++;
                }
            }
        }

        $reader->close();

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * Herkent de kolomposities op basis van de kopregel.
     *
     * @param  array<int, mixed>  $headerRow
     * @return array<string, int>
     */
    private function detectColumns(array $headerRow): array
    {
        $columns = [];

        foreach ($headerRow as $index => $value) {
            $header = mb_strtolower(trim((string) $value));

            if ($header === '') {
                continue;
            }

            $columns += match (true) {
                str_contains($header, 'datum') => ['date' => $index],
                str_contains($header, 'begin') => ['start_time' => $index],
                str_contains($header, 'eind') => ['end_time' => $index],
                str_contains($header, 'pauze') => ['break_minutes' => $index],
                str_contains($header, 'schrijving') => ['description' => $index],
                default => [],
            };
        }

        return $columns;
    }

    /**
     * Maakt één tijdregistratie aan. Geeft false terug als de rij niet
     * bruikbaar is (geen datum, of validatiefout zoals overlap).
     *
     * @param  array<int, mixed>  $values
     * @param  array<string, int>  $columns
     */
    private function createEntry(array $values, array $columns, User $user): bool
    {
        $date = $this->toDate($this->value($values, $columns, 'date'));

        if ($date === null) {
            return false;
        }

        $startTime = $this->toTime($this->value($values, $columns, 'start_time'));
        $endTime = $this->toTime($this->value($values, $columns, 'end_time'));

        // Zonder begin- en eindtijd is dit een afwezigheidsdag.
        $isAbsent = $startTime === null && $endTime === null;

        try {
            TimeEntry::create([
                'user_id' => $user->id,
                'date' => $date->toDateString(),
                'start_time' => $isAbsent ? null : $startTime,
                'end_time' => $isAbsent ? null : $endTime,
                'break_minutes' => $isAbsent ? 0 : $this->toInt($this->value($values, $columns, 'break_minutes')),
                'description' => $this->value($values, $columns, 'description'),
                'is_absent' => $isAbsent,
            ]);
        } catch (ValidationException) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<int, mixed>  $values
     * @param  array<string, int>  $columns
     */
    private function value(array $values, array $columns, string $key): mixed
    {
        if (! isset($columns[$key])) {
            return null;
        }

        return $values[$columns[$key]] ?? null;
    }

    private function toDate(mixed $value): ?Carbon
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d-m-Y', trim((string) $value));
        } catch (\Throwable) {
            return null;
        }
    }

    private function toTime(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i');
        }

        if (blank($value)) {
            return null;
        }

        $value = trim((string) $value);

        if (preg_match('/^\d{1,2}:\d{2}$/', $value)) {
            [$hours, $minutes] = explode(':', $value);

            return sprintf('%02d:%02d', $hours, $minutes);
        }

        return null;
    }

    private function toInt(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        return 0;
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function isEmptyRow(array $values): bool
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return false;
            }
        }

        return true;
    }
}
