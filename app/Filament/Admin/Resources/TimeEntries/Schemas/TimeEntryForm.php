<?php

namespace App\Filament\Admin\Resources\TimeEntries\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class TimeEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Gebruiker')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->default(auth()->id())
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),

                DatePicker::make('date')
                    ->label('Datum')
                    ->displayFormat('dd-mm-YYYY')
                    ->format('Y-m-d')
                    ->required(),

                TimePicker::make('start_time')
                    ->label('Starttijd')
                    ->required()
                    ->seconds(false)
                    ->displayFormat('H:i')
                    ->format('H:i'),

                TimePicker::make('end_time')
                    ->label('Eindtijd')
                    ->required()
                    ->seconds(false)
                    ->displayFormat('H:i')
                    ->format('H:i'),

                TextInput::make('break_minutes')
                    ->label('Pauze (minuten)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(1440)
                    ->default(0)
                    ->required(),

                Textarea::make('description')
                    ->label('Beschrijving')
                    ->rows(3)
                    ->columnSpanFull(),

            ]);
    }
}
