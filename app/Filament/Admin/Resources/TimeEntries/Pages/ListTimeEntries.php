<?php

namespace App\Filament\Admin\Resources\TimeEntries\Pages;

use App\Filament\Admin\Resources\TimeEntries\TimeEntryResource;
use App\Filament\Exports\TimeEntryExporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Resources\Pages\ListRecords;

class ListTimeEntries extends ListRecords
{
    protected static string $resource = TimeEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(TimeEntryExporter::class)
                ->formats([ExportFormat::Xlsx])
                ->label('Exporteren (.xlsx)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger'),

            CreateAction::make(),
        ];
    }
}
