<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class WorkbookService
{
    protected static bool $autoRefreshDisabled = false;

    /**
     * Schakelt het automatisch bijwerken (model events) tijdelijk uit,
     * bijvoorbeeld tijdens een bulk-sync. Geef een callback mee; daarna
     * ververst de aanroeper zelf één keer.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function withoutAutoRefresh(callable $callback): mixed
    {
        static::$autoRefreshDisabled = true;

        try {
            return $callback();
        } finally {
            static::$autoRefreshDisabled = false;
        }
    }

    public function isLinked(User $user): bool
    {
        return $user->hasLinkedWorkbook();
    }

    public function link(User $user): void
    {
        $user->forceFill(['workbook_linked_at' => now()])->save();

        $this->generate($user);
    }

    public function unlink(User $user): void
    {
        $user->forceFill(['workbook_linked_at' => null])->save();

        Storage::disk('local')->delete($this->relativePath($user));
    }

    /**
     * Werkt het gekoppelde werkblad bij (alleen als het gebruiker gekoppeld is).
     */
    public function refresh(User $user): void
    {
        if (! $this->isLinked($user)) {
            return;
        }

        $this->generate($user);
    }

    /**
     * Hook voor model events: negeert updates tijdens bulk-operaties.
     */
    public function refreshQuietly(?User $user): void
    {
        if ($user === null || static::$autoRefreshDisabled) {
            return;
        }

        $this->refresh($user);
    }

    /**
     * Genereert het persoonlijke Excel-werkblad met alle uren + totalen.
     */
    public function generate(User $user): string
    {
        $entries = $user->timeEntries()
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        Storage::disk('local')->makeDirectory(dirname($this->relativePath($user)));

        $writer = new Writer;
        $writer->openToFile($this->absolutePath($user));

        $writer->addRow(Row::fromValues([
            'Datum', 'Begintijd', 'Eindtijd', 'Pauze (min)', 'Duur', 'Beschrijving',
        ]));

        $totalMinutes = 0;

        foreach ($entries as $entry) {
            $minutes = $entry->duration;
            $totalMinutes += $minutes;

            $writer->addRow(Row::fromValues([
                $entry->date->format('d-m-Y'),
                $entry->start_time->format('H:i'),
                $entry->end_time->format('H:i'),
                $entry->break_minutes,
                sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60),
                (string) $entry->description,
            ]));
        }

        $writer->addRow(Row::fromValues([
            '', '', '', '', sprintf('%02d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60),
            'Totaal',
        ]));

        $writer->close();

        return $this->absolutePath($user);
    }

    public function exists(User $user): bool
    {
        return Storage::disk('local')->exists($this->relativePath($user));
    }

    public function absolutePath(User $user): string
    {
        return Storage::disk('local')->path($this->relativePath($user));
    }

    public function downloadName(User $user): string
    {
        $slug = str($user->name)->slug()->lower()->limit(40, '');

        return "stage-uren-{$slug}.xlsx";
    }

    protected function relativePath(User $user): string
    {
        return "workbooks/{$user->id}/stage-uren.xlsx";
    }
}
