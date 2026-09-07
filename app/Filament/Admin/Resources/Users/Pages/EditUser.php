<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Enums\Role;
use App\Filament\Admin\Resources\Users\UserResource;
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
                ->title('Rol kan niet worden gewijzigd')
                ->body('Er moet altijd minimaal één beheerder blijven.')
                ->danger()
                ->send();

            $this->halt();

            return $data;
        }

        return $data;
    }
}
