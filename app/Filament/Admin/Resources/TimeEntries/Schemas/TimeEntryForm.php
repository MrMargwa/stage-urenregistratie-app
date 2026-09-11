<?php

namespace App\Filament\Admin\Resources\TimeEntries\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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

                Checkbox::make('is_absent')
                    ->label('Ik was deze dag niet aanwezig')
                    ->helperText('Bijv. ziek, vakantie of vrij. Beschrijf dit in de omschrijving.')
                    ->live()
                    ->afterStateUpdated(function (Set $set, bool $state): void {
                        if ($state) {
                            $set('start_time', null);
                            $set('end_time', null);
                            $set('break_minutes', 0);
                        }
                    }),

                TimePicker::make('start_time')
                    ->label('Starttijd')
                    ->required(fn (Get $get): bool => ! (bool) $get('is_absent'))
                    ->visible(fn (Get $get): bool => ! (bool) $get('is_absent'))
                    ->seconds(false)
                    ->displayFormat('H:i')
                    ->format('H:i'),

                TimePicker::make('end_time')
                    ->label('Eindtijd')
                    ->required(fn (Get $get): bool => ! (bool) $get('is_absent'))
                    ->visible(fn (Get $get): bool => ! (bool) $get('is_absent'))
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
                    ->required(fn (Get $get): bool => ! (bool) $get('is_absent'))
                    ->visible(fn (Get $get): bool => ! (bool) $get('is_absent')),

                Textarea::make('description')
                    ->label('Beschrijving')
                    ->rows(3)
                    ->columnSpanFull(),

            ]);
    }
}
