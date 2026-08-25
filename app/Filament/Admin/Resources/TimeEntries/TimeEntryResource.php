<?php

namespace App\Filament\Admin\Resources\TimeEntries;

use App\Enums\Role;
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
use Illuminate\Database\Eloquent\Builder;

class TimeEntryResource extends Resource
{
    protected static ?string $model = TimeEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Tijdregistraties';

    protected static ?string $modelLabel = 'Tijdregistratie';

    protected static ?string $pluralModelLabel = 'Tijdregistraties';

    public static function form(Schema $schema): Schema
    {
        return TimeEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TimeEntriesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        if (! $user->getRoleEnum()->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
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
