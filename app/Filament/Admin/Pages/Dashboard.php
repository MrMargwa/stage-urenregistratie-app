<?php

namespace App\Filament\Admin\Pages;

use App\Models\TimeEntry;
use App\Helpers\DurationHelper;
use App\Services\ExportService;
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

    private ?\Illuminate\Support\Collection $cachedWeekEntries = null;

    public function mount(): void
    {
        $this->weekStart = Carbon::now()->startOfWeek()->format('Y-m-d');
    }

    public function dehydrate(): void
    {
        $this->cachedWeekEntries = null;
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
            Text::make('Exporteer je tijdregistraties als Excel')
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
            ->records(fn () => $this->getWeekEntries())
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
                    ->formatStateUsing(fn (int $state): string => DurationHelper::formatMinutes($state))
                    ->weight('bold'),
            ])
            ->defaultSort('date', 'asc')
            ->paginated([10, 25, 50])
            ->searchable(false);
    }

    public function exportWeek(): StreamedResponse
    {
        $entries = app(ExportService::class)->getEntriesForWeek(auth()->user(), $this->weekStart);
        $start = Carbon::parse($this->weekStart);

        return app(ExportService::class)->exportToXlsx(
            $entries,
            'uren_week_' . $start->format('Y-m-d') . '.xlsx',
            auth()->user()->exportColors(),
        );
    }

    public function exportAll(): StreamedResponse
    {
        $entries = app(ExportService::class)->getAllEntries(auth()->user());

        return app(ExportService::class)->exportToXlsx(
            $entries,
            'uren_allemaal.xlsx',
            auth()->user()->exportColors(),
        );
    }

    private function getWeekEntries(): \Illuminate\Support\Collection
    {
        if ($this->cachedWeekEntries !== null) {
            return $this->cachedWeekEntries;
        }

        $start = Carbon::parse($this->weekStart);
        $end = $start->copy()->endOfWeek();

        $this->cachedWeekEntries = TimeEntry::where('user_id', auth()->id())
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return $this->cachedWeekEntries;
    }

    public function getWeekEntriesProperty(): array
    {
        $start = Carbon::parse($this->weekStart);
        $end = $start->copy()->endOfWeek();

        $entries = $this->getWeekEntries();

        $days = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dayEntries = $entries->filter(fn (TimeEntry $e) => $e->date->isSameDay($date));
            $totalMinutes = (int) $dayEntries->sum('duration');

            $days[] = [
                'date' => $date->copy(),
                'entries' => $dayEntries,
                'total_minutes' => $totalMinutes,
                'total_hours' => DurationHelper::formatMinutes($totalMinutes),
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
        return DurationHelper::formatMinutes($this->totalMinutes);
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
