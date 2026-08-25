<?php

namespace App\Filament\Admin\Resources\TimeEntries\Tables;

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
                    ->options(fn () => TimeEntry::query()
                        ->select('date')
                        ->get()
                        ->mapWithKeys(fn ($entry) => [
                            $entry->date->format('Y-m') => $entry->date->locale('nl')->translatedFormat('F Y'),
                        ])
                        ->unique()
                        ->sort()
                        ->all())
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'] ?? null)) {
                            $year = substr($data['value'], 0, 4);
                            $month = substr($data['value'], 5, 2);
                            $query->whereYear('date', $year)
                                ->whereMonth('date', $month);
                        }
                    }),

                SelectFilter::make('week')
                    ->label('Week')
                    ->options(fn () => TimeEntry::query()
                        ->select('date')
                        ->get()
                        ->mapWithKeys(fn ($entry) => [
                            $entry->date->format('o') . '-' . $entry->date->isoWeek() => $entry->date->format('o') . ' – week ' . $entry->date->isoWeek(),
                        ])
                        ->unique()
                        ->sort()
                        ->all())
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'] ?? null)) {
                            $parts = explode('-', $data['value']);
                            $year = $parts[0];
                            $week = $parts[1];
                            $start = Carbon::now()->setISODate((int) $year, (int) $week)->startOfWeek();
                            $end = $start->copy()->endOfWeek();
                            $query->whereBetween('date', [$start, $end]);
                        }
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
}
