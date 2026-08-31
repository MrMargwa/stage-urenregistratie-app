<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Enums\Role;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Naam')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('E-mailadres')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn (Role $state): string => $state->label())
                    ->color(fn (Role $state): string => $state->isAdmin() ? 'danger' : 'gray')
                    ->sortable(),

                TextColumn::make('time_entries_count')
                    ->label('Urenregistraties')
                    ->counts('timeEntries')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Aangemaakt op')
                    ->date('d-m-Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rol')
                    ->options(Role::options()),
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
