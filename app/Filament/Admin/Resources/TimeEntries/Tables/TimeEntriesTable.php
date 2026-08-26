<?php

namespace App\Filament\Admin\Resources\TimeEntries\Tables;

use App\Filament\Admin\Resources\TimeEntries\TimeEntryResource;
use App\Helpers\DurationHelper;
use App\Models\TimeEntry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                    ->formatStateUsing(fn ($state) => DurationHelper::formatMinutes($state)),
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
