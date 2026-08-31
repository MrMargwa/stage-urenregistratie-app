<?php

namespace App\Filament\Admin\Resources\TimeEntries\Schemas;

use Filament\Forms\Components\DatePicker;
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
                    ->format('H:i')
                    ->afterOrEqual('start_time'),

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
