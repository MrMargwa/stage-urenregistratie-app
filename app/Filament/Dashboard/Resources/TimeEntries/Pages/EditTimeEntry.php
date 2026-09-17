<?php

namespace App\Filament\Dashboard\Resources\TimeEntries\Pages;

use App\Filament\Dashboard\Resources\TimeEntries\TimeEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTimeEntry extends EditRecord
{
    protected static string $resource = TimeEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
