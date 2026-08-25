<?php

namespace App\Filament\Admin\Resources\TimeEntries\Pages;

use App\Filament\Admin\Actions\SyncTimeEntriesAction;
use App\Filament\Admin\Actions\WorkbookActions;
use App\Filament\Admin\Resources\TimeEntries\TimeEntryResource;
use App\Filament\Exports\TimeEntryExporter;
use App\Models\TimeEntry;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Response;

class ListTimeEntries extends ListRecords
{
    protected static string $resource = TimeEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...WorkbookActions::forHeader(),

            ExportAction::make()
                ->exporter(TimeEntryExporter::class)
                ->formats([ExportFormat::Xlsx])
                ->label('Exporteren')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger'),

            SyncTimeEntriesAction::make(),

            CreateAction::make(),
        ];
    }

    protected function exportPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $user = auth()->user();
        $entries = TimeEntry::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->get();

        $html = $this->buildPdfHtml($entries);

        return Response::make($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="tijdregistraties.pdf"',
        ]);
    }

    protected function exportCsv(): \Symfony\Component\HttpFoundation\Response
    {
        $user = auth()->user();
        $entries = TimeEntry::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->get();

        $csv = "Datum,Begintijd,Eindtijd,Pauze (minuten),Beschrijving,Duur\n";

        foreach ($entries as $entry) {
            $duration = sprintf('%02d:%02d', intdiv($entry->duration, 60), $entry->duration % 60);
            $csv .= implode(',', [
                $entry->date->format('d-m-Y'),
                $entry->start_time->format('H:i'),
                $entry->end_time->format('H:i'),
                $entry->break_minutes,
                '"' . str_replace('"', '""', $entry->description ?? '') . '"',
                $duration,
            ]) . "\n";
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="tijdregistraties.csv"',
        ]);
    }

    protected function buildPdfHtml($entries): string
    {
        $totalMinutes = $entries->sum('duration');
        $totalHours = sprintf('%02d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60);

        $rows = '';
        foreach ($entries as $entry) {
            $duration = sprintf('%02d:%02d', intdiv($entry->duration, 60), $entry->duration % 60);
            $rows .= '<tr>'
                . '<td>' . $entry->date->format('d-m-Y') . '</td>'
                . '<td>' . $entry->start_time->format('H:i') . '</td>'
                . '<td>' . $entry->end_time->format('H:i') . '</td>'
                . '<td>' . $entry->break_minutes . '</td>'
                . '<td>' . e($entry->description ?? '') . '</td>'
                . '<td>' . $duration . '</td>'
                . '</tr>';
        }

        return '<!DOCTYPE html><html><head><meta charset="utf-8">'
            . '<style>body{font-family:sans-serif;padding:40px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f5f5f5}.total{margin-top:16px;font-weight:bold}</style>'
            . '</head><body>'
            . '<h1>Tijdregistraties</h1>'
            . '<table><thead><tr><th>Datum</th><th>Begintijd</th><th>Eindtijd</th><th>Pauze</th><th>Beschrijving</th><th>Duur</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody></table>'
            . '<p class="total">Totaal: ' . $totalHours . '</p>'
            . '</body></html>';
    }
}
