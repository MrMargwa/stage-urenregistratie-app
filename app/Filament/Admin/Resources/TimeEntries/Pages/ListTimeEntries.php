<?php

namespace App\Filament\Admin\Resources\TimeEntries\Pages;

use App\Filament\Admin\Resources\TimeEntries\TimeEntryResource;
use App\Filament\Exports\TimeEntryExporter;
use App\Models\TimeEntry;
use App\Services\TimeEntryImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

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

            Action::make('import')
                ->label('Importeren (.xlsx)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->visible(fn (): bool => ! TimeEntry::ownedBy(auth()->user())->exists())
                ->modalHeading('Tijdregistraties importeren')
                ->modalDescription('Kies een .xlsx-bestand dat je eerder met de exportknop hebt gemaakt. De uren worden aan jouw account toegevoegd.')
                ->modalSubmitActionLabel('Importeren')
                ->form([
                    FileUpload::make('file')
                        ->label('Excel-bestand')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->disk('local')
                        ->directory('imports')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $stored = $data['file'];
                    $stored = is_array($stored) ? reset($stored) : $stored;

                    $result = app(TimeEntryImporter::class)->import(
                        Storage::disk('local')->path($stored),
                        auth()->user(),
                    );

                    Storage::disk('local')->delete($stored);

                    Notification::make()
                        ->title('Import voltooid')
                        ->body("{$result['imported']} registratie(s) geïmporteerd, {$result['skipped']} overgeslagen.")
                        ->success()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }
}
