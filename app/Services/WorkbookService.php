<?php

namespace App\Services;

use App\Helpers\DurationHelper;
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

    public function link(User $user, ?string $customPath = null): void
    {
        $user->forceFill([
            'workbook_linked_at' => now(),
            'workbook_path' => $customPath,
        ])->save();

        $this->generate($user);
    }

    public function unlink(User $user): void
    {
        $path = $user->workbook_path;

        $user->forceFill([
            'workbook_linked_at' => null,
            'workbook_path' => null,
        ])->save();

        if ($path && file_exists($path)) {
            unlink($path);
        } else {
            Storage::disk('local')->delete($this->relativePath($user));
        }
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

        $targetPath = $user->workbook_path;

        if ($targetPath) {
            $dir = dirname($targetPath);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        } else {
            $targetPath = $this->absolutePath($user);
            Storage::disk('local')->makeDirectory(dirname($this->relativePath($user)));
        }

        $writer = new Writer;
        $writer->openToFile($targetPath);

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
                DurationHelper::formatMinutes($minutes),
                (string) $entry->description,
            ]));
        }

        $writer->addRow(Row::fromValues([
            '', '', '', '', DurationHelper::formatMinutes($totalMinutes),
            'Totaal',
        ]));

        $writer->close();

        return $targetPath;
    }

    public function exists(User $user): bool
    {
        $path = $user->workbook_path;
        if ($path) {
            return file_exists($path);
        }

        return Storage::disk('local')->exists($this->relativePath($user));
    }

    public function absolutePath(User $user): string
    {
        $customPath = $user->workbook_path;
        if ($customPath) {
            return $customPath;
        }

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
