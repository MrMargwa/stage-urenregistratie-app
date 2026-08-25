<?php

namespace App\Filament\Admin\Pages;

use App\Models\TimeEntry;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Dashboard extends \Filament\Pages\Dashboard implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard';

    public ?string $weekStart = null;

    public function mount(): void
    {
        $this->weekStart = Carbon::now()->startOfWeek()->format('Y-m-d');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.widgets.progress-bar'),
            $this->makeButtonRow(),
            Text::make($this->weekLabel)
                ->size('lg')
                ->weight('bold'),
            Text::make('Totaal deze week: ' . $this->totalHours)
                ->weight('bold')
                ->color('primary'),
            EmbeddedTable::make(),
            $this->makeExportRow(),
        ]);
    }

    protected function makeButtonRow(): Flex
    {
        return Flex::make([
            Flex::make([
                Action::make('previousWeek')
                    ->label('Vorige week')
                    ->icon(Heroicon::OutlinedChevronLeft)
                    ->color('gray')
                    ->action('previousWeek'),
                Action::make('currentWeek')
                    ->label('Huidige week')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->color('primary')
                    ->action('currentWeek'),
                Action::make('nextWeek')
                    ->label('Volgende week')
                    ->icon(Heroicon::OutlinedChevronRight)
                    ->color('gray')
                    ->action('nextWeek'),
            ]),
            Action::make('createTimeEntry')
                    ->label('+ Tijdregistratie')
                    ->url(fn (): string => route('filament.admin.resources.time-entries.create'))
                    ->color('primary'),
        ])->alignment(Alignment::Between);
    }

    protected function makeExportRow(): Flex
    {
        return Flex::make([
            Text::make('Exporteer je tijdregistraties als CSV-bestand')
                ->color('gray'),
            Action::make('exportWeek')
                ->label('Exporteer week')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('primary')
                ->action('exportWeek'),
            Action::make('exportAll')
                ->label('Exporteer alles')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action('exportAll'),
        ])->alignment(Alignment::End);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => TimeEntry::where('user_id', auth()->id())
                ->whereBetween('date', [
                    Carbon::parse($this->weekStart),
                    Carbon::parse($this->weekStart)->copy()->endOfWeek(),
                ])
                ->orderBy('date')
                ->orderBy('start_time')
                ->get()
            )
            ->columns([
                TextColumn::make('date')
                    ->label('Datum')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('day_name')
                    ->label('Dag')
                    ->state(fn (TimeEntry $record): string => $record->date->translatedFormat('l')),
                TextColumn::make('start_time')
                    ->label('Start')
                    ->time('H:i'),
                TextColumn::make('end_time')
                    ->label('Eind')
                    ->time('H:i'),
                TextColumn::make('break_minutes')
                    ->label('Pauze')
                    ->formatStateUsing(fn (int $state): string => $state . ' min'),
                TextColumn::make('description')
                    ->label('Omschrijving')
                    ->limit(50)
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('duration')
                    ->label('Duur')
                    ->formatStateUsing(fn (int $state): string => sprintf('%02d:%02d', intdiv($state, 60), $state % 60))
                    ->weight('bold'),
            ])
            ->defaultSort('date', 'asc')
            ->paginated([10, 25, 50])
            ->searchable(false);
    }

    public function exportWeek(): StreamedResponse
    {
        $start = Carbon::parse($this->weekStart);
        $end = $start->copy()->endOfWeek();

        $entries = TimeEntry::where('user_id', auth()->id())
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return $this->buildCsvDownload($entries, 'uren_week_' . $start->format('Y-m-d') . '.csv');
    }

    public function exportAll(): StreamedResponse
    {
        $entries = TimeEntry::where('user_id', auth()->id())
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return $this->buildCsvDownload($entries, 'uren_allemaal.csv');
    }

    protected function buildCsvDownload($entries, string $filename): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($entries) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Datum', 'Dag', 'Start', 'Eind', 'Pauze (min)', 'Omschrijving', 'Duur (uren)']);

            foreach ($entries as $entry) {
                fputcsv($handle, [
                    $entry->date->format('d-m-Y'),
                    $entry->date->translatedFormat('l'),
                    $entry->start_time->format('H:i'),
                    $entry->end_time->format('H:i'),
                    $entry->break_minutes,
                    $entry->description ?? '',
                    sprintf('%02d:%02d', intdiv($entry->duration, 60), $entry->duration % 60),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function getWeekEntriesProperty(): array
    {
        $start = Carbon::parse($this->weekStart);
        $end = $start->copy()->endOfWeek();

        $entries = TimeEntry::where('user_id', auth()->id())
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $days = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dayEntries = $entries->filter(fn (TimeEntry $e) => $e->date->isSameDay($date));
            $totalMinutes = $dayEntries->sum('duration');

            $days[] = [
                'date' => $date->copy(),
                'entries' => $dayEntries,
                'total_minutes' => $totalMinutes,
                'total_hours' => sprintf('%02d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60),
                'is_today' => $date->isToday(),
            ];
        }

        return $days;
    }

    public function getTotalMinutesProperty(): int
    {
        return collect($this->weekEntries)->sum('total_minutes');
    }

    public function getTotalHoursProperty(): string
    {
        $total = $this->totalMinutes;

        return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
    }

    public function getWeekLabelProperty(): string
    {
        $start = Carbon::parse($this->weekStart);
        $end = $start->copy()->endOfWeek();

        return $start->format('d M') . ' – ' . $end->format('d M Y') . '  (week ' . $start->isoWeek() . ')';
    }

    public function previousWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->startOfWeek()->format('Y-m-d');
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->startOfWeek()->format('Y-m-d');
    }

    public function currentWeek(): void
    {
        $this->weekStart = Carbon::now()->startOfWeek()->format('Y-m-d');
    }
}
