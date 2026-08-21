<?php

namespace App\Filament\Exports;

use App\Models\TimeEntry;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class TimeEntryExporter extends Exporter
{
    protected static ?string $model = TimeEntry::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('date')
                ->label('Datum')
                ->formatStateUsing(fn ($state) => $state->format('d-m-Y')),

            ExportColumn::make('start_time')
                ->label('Begintijd')
                ->formatStateUsing(fn ($state) => $state->format('H:i')),

            ExportColumn::make('end_time')
                ->label('Eindtijd')
                ->formatStateUsing(fn ($state) => $state->format('H:i')),

            ExportColumn::make('break_minutes')
                ->label('Pauze (minuten)'),

            ExportColumn::make('description')
                ->label('Beschrijving'),

            ExportColumn::make('duration')
                ->label('Duur')
                ->formatStateUsing(fn ($state) => sprintf('%02d:%02d', intdiv($state, 60), $state % 60)),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Je export is klaar, '.$export->successful_rows.' rijen geëxporteerd.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.$failedRowsCount.' rijen zijn mislukt.';
        }

        return $body;
    }
}
