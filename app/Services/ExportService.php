<?php

namespace App\Services;

use App\Helpers\DurationHelper;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ExportService
{
    private const HEADERS = [
        'Weeknummer',
        'Datum',
        'Begintijd',
        'Eindtijd',
        'Pauze',
        'Beschrijving',
        'Duur',
    ];

    public function getEntriesForWeek(User $user, string $weekStart): Collection
    {
        $start = Carbon::parse($weekStart);
        $end = $start->copy()->endOfWeek();

        return $this->getBaseQuery($user)
            ->whereBetween('date', [$start, $end])
            ->get();
    }

    public function getAllEntries(User $user): Collection
    {
        return $this->getBaseQuery($user)->get();
    }

    public function exportToCsv(Collection $entries, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($entries) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, self::HEADERS);

            foreach ($entries as $entry) {
                fputcsv($handle, $this->mapEntryToRow($entry));
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function exportToXlsx(
        Collection $entries,
        string $filename,
        ?array $accentColor = null,
    ): \Symfony\Component\HttpFoundation\StreamedResponse {
        return response()->stream(function () use ($entries, $filename, $accentColor) {
            $writer = new Writer();
            $writer->openToBrowser($filename);

            $headerStyle = $this->buildHeaderStyle($accentColor);

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(self::HEADERS)->setStyle($headerStyle));

            foreach ($entries as $entry) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($this->mapEntryToRow($entry)));
            }

            $writer->close();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * @return array<int, mixed>
     */
    private function mapEntryToRow(TimeEntry $entry): array
    {
        return [
            $entry->date->isoWeek(),
            $entry->date->format('d-m-Y'),
            $entry->start_time->format('H:i'),
            $entry->end_time->format('H:i'),
            $entry->break_minutes,
            $entry->description ?? '',
            DurationHelper::formatMinutes($entry->duration),
        ];
    }

    private function getBaseQuery(User $user)
    {
        return $user->timeEntries()
            ->orderBy('date')
            ->orderBy('start_time');
    }

    private function buildHeaderStyle(?array $accentColor): Style
    {
        $style = new Style();
        $style->setFontBold();
        $style->setFontSize(11);

        $bgColor = $accentColor['bg'] ?? '4472C4';
        $fontColor = $accentColor['font'] ?? 'FFFFFF';

        $style->setBackgroundColor($bgColor);
        $style->setFontColor($fontColor);

        $border = new Border(
            new BorderPart(Border::BOTTOM, 'CCCCCC', Border::WIDTH_THIN, Border::STYLE_SOLID),
        );
        $style->setBorder($border);

        return $style;
    }
}
