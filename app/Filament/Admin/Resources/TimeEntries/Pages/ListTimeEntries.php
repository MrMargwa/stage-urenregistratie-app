<?php

namespace App\Filament\Admin\Resources\TimeEntries\Pages;

use App\Filament\Admin\Resources\TimeEntries\TimeEntryResource;
use App\Filament\Exports\TimeEntryExporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListTimeEntries extends ListRecords
{
    protected static string $resource = TimeEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(TimeEntryExporter::class)
                ->label('Exporteren')
                ->color('success'),

            CreateAction::make(),
        ];
    }
}
