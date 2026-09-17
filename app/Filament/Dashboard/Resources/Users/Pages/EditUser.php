<?php

namespace App\Filament\Dashboard\Resources\Users\Pages;

use App\Enums\Role;
use App\Filament\Dashboard\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->isLastAdmin()
            && ($data['role'] ?? null) !== Role::Admin->value) {
            Notification::make()
                ->title('Rol niet gewijzigd')
                ->body('Er moet altijd minimaal één beheerder zijn. Omdat dit de laatste beheerder is, kan de rol niet worden aangepast.')
                ->danger()
                ->send();

            $this->halt();

            return $data;
        }

        return $data;
    }
}
