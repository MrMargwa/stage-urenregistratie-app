<?php

namespace App\Filament\Admin\Resources\TimeEntries\Tables;

use App\Filament\Admin\Resources\TimeEntries\TimeEntryResource;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
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
                TextColumn::make('user.name')
                    ->label('Gebruiker')
                    ->sortable()
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),

                TextColumn::make('date')
                    ->label('Datum')
                    ->date('d-m-Y'),

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
                    ->tooltip(fn ($state) => $state),

                TextColumn::make('duration')
                    ->label('Duur')
                    ->formatStateUsing(fn ($state) => sprintf('%02d:%02d', intdiv($state, 60), $state % 60)),
            ])
            ->filters([
                SelectFilter::make('maand')
                    ->label('Maand')
                    ->options(fn () => self::maandOpties())
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;

                        if (! filled($value)) {
                            return;
                        }

                        [$from, $till] = self::maandBereik($value);
                        $query->whereBetween('date', [$from, $till]);
                    }),

                SelectFilter::make('week')
                    ->label('Week')
                    ->options(fn () => self::weekOpties())
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;

                        if (! filled($value)) {
                            return;
                        }

                        [$from, $till] = self::weekBereik($value);
                        $query->whereBetween('date', [$from, $till]);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** @return array<string, string> */
    protected static function maandOpties(): array
    {
        return TimeEntryResource::getEloquentQuery()
            ->get(['date'])
            ->sortBy('date')
            ->unique(fn (TimeEntry $entry) => $entry->date->format('Y-m'))
            ->mapWithKeys(fn (TimeEntry $entry) => [
                $entry->date->format('Y-m') => $entry->date->locale('nl')->translatedFormat('F Y'),
            ])
            ->all();
    }

    /** @return array<string, string> */
    protected static function weekOpties(): array
    {
        return TimeEntryResource::getEloquentQuery()
            ->get(['date'])
            ->sortBy('date')
            ->unique(fn (TimeEntry $entry) => $entry->date->format('o-W'))
            ->mapWithKeys(fn (TimeEntry $entry) => [
                $entry->date->format('o-W') => $entry->date->format('o').' – week '.(int) $entry->date->format('W'),
            ])
            ->all();
    }

    /** @return array{0: string, 1: string} */
    protected static function maandBereik(string $maand): array
    {
        $start = Carbon::createFromFormat('Y-m', $maand)->startOfDay();
        $eind = (clone $start)->endOfMonth();

        return [$start->toDateString(), $eind->toDateString()];
    }

    /** @return array{0: string, 1: string} */
    protected static function weekBereik(string $week): array
    {
        [$jaar, $weeknummer] = explode('-', $week);

        $start = Carbon::create()->setISODate((int) $jaar, (int) $weeknummer)
            ->startOfWeek(Carbon::MONDAY)
            ->startOfDay();
        $eind = (clone $start)->addDays(6)->endOfDay();

        return [$start->toDateString(), $eind->toDateString()];
    }
}
