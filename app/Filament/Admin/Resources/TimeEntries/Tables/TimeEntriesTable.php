<?php

namespace App\Filament\Admin\Resources\TimeEntries\Tables;

use App\Helpers\DurationHelper;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TimeEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Datum')
                    ->date('d-m-Y')
                    ->sortable(),

                TextColumn::make('start_time')
                    ->label('Begintijd')
                    ->time('H:i'),

                TextColumn::make('end_time')
                    ->label('Eindtijd')
                    ->time('H:i'),

                TextColumn::make('break_minutes')
                    ->label('Pauze (minuten)'),

                TextColumn::make('description')
                    ->label('Beschrijving')
                    ->limit(40)
                    ->tooltip(fn (TimeEntry $record): string => $record->description),

                TextColumn::make('duration_minutes')
                    ->label('Duur')
                    ->formatStateUsing(fn (?int $state) => DurationHelper::formatMinutes($state ?? 0)),
            ])
            ->filters([
                SelectFilter::make('week')
                    ->label('Weekstaat')
                    ->options(fn (): array => self::weekOptions())
                    ->query(function (Builder $query, array $data): void {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return;
                        }

                        $start = Carbon::parse($value)->startOfWeek();
                        $end = $start->copy()->endOfWeek();

                        $query->whereBetween('date', [$start, $end]);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->emptyStateHeading('Nog geen tijdregistraties')
            ->emptyStateDescription('Zodra je uren invult, verschijnen ze hier. Begin met je eerste registratie.')
            ->emptyStateIcon('heroicon-o-clock')
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }

    /**
     * Bouwt de lijst van beschikbare weken voor de filter, gebaseerd op de
     * datums die in de eigen registraties van de gebruiker voorkomen
     * (van oudste tot nieuwste week). Iedereen ziet alleen eigen uren.
     *
     * @return array<string, string>
     */
    private static function weekOptions(): array
    {
        $dates = TimeEntry::query()
            ->ownedBy(auth()->user())
            ->select('date')
            ->distinct()
            ->orderBy('date')
            ->get()
            ->pluck('date');

        return $dates->mapWithKeys(function ($date): array {
            $carbon = Carbon::parse($date);
            $start = $carbon->copy()->startOfWeek();
            $key = $start->toDateString();
            $label = 'Week '.$start->isoWeek().' · '.$start->format('d M Y');

            return [$key => $label];
        })->unique()->toArray();
    }
}
