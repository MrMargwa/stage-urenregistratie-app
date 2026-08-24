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
                        ->selectRaw("DISTINCT DATE_FORMAT(`date`, '%Y-%m') AS ym")
                        ->orderBy('ym')
                        ->pluck('ym')
                        ->mapWithKeys(fn ($ym) => [
                            $ym => Carbon::createFromFormat('Y-m', $ym)->locale('nl')->translatedFormat('F Y'),
                        ])
                        ->all())
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'] ?? null)) {
                            $query->whereRaw("DATE_FORMAT(`date`, '%Y-%m') = ?", [$data['value']]);
                        }
                    }),

                SelectFilter::make('week')
                    ->label('Week')
                    ->options(fn () => TimeEntry::query()
                        ->selectRaw('DISTINCT YEARWEEK(`date`, 3) AS yw')
                        ->orderBy('yw')
                        ->pluck('yw')
                        ->mapWithKeys(fn ($yw) => [
                            $yw => substr((string) $yw, 0, 4).' – week '.substr((string) $yw, 4),
                        ])
                        ->all())
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'] ?? null)) {
                            $query->whereRaw('YEARWEEK(`date`, 3) = ?', [$data['value']]);
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
