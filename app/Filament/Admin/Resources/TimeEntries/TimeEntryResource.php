<?php

namespace App\Filament\Admin\Resources\TimeEntries;

use App\Filament\Admin\Resources\TimeEntries\Pages\CreateTimeEntry;
use App\Filament\Admin\Resources\TimeEntries\Pages\EditTimeEntry;
use App\Filament\Admin\Resources\TimeEntries\Pages\ListTimeEntries;
use App\Filament\Admin\Resources\TimeEntries\Schemas\TimeEntryForm;
use App\Filament\Admin\Resources\TimeEntries\Tables\TimeEntriesTable;
use App\Models\TimeEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TimeEntryResource extends Resource
{
    protected static ?string $model = TimeEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TimeEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TimeEntriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTimeEntries::route('/'),
            'create' => CreateTimeEntry::route('/create'),
            'edit' => EditTimeEntry::route('/{record}/edit'),
        ];
    }
}
