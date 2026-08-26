<?php

namespace App\Filament\Admin\Actions;

use App\Models\User;
use App\Services\TimeEntrySyncService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SyncTimeEntriesAction extends Action
{
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'sync')
            ->label('Excel synchroniseren')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->form(self::getFormSchema())
            ->modalHeading('Uren synchroniseren vanuit Excel')
            ->modalDescription(
                'Upload een Excel- of CSV-bestand. Bestaande registraties worden bijgewerkt en nieuwe worden aangemaakt, '
                .'gebaseerd op datum en begintijd.'
            )
            ->modalSubmitActionLabel('Synchroniseren')
            ->action(function (array $data): void {
                /** @var User $user */
                $user = isset($data['user_id']) ? User::findOrFail($data['user_id']) : auth()->user();

                $disk = Storage::disk('local');
                $path = $disk->path($data['file']);

                try {
                    $result = app(TimeEntrySyncService::class)->syncFromFile(
                        $user,
                        $path,
                        deleteMissing: (bool) ($data['delete_missing'] ?? false),
                    );
                } catch (RuntimeException $exception) {
                    Notification::make()
                        ->title('Synchronisatie mislukt')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                } finally {
                    if (isset($data['file'])) {
                        $disk->delete($data['file']);
                    }
                }

                $body = $result->summary();

                if ($result->errors !== []) {
                    $body .= "\n\n".implode("\n", array_slice($result->errors, 0, 5));

                    if (count($result->errors) > 5) {
                        $body .= sprintf("\n… en %d fouten meer.", count($result->errors) - 5);
                    }
                }

                Notification::make()
                    ->title('Synchronisatie voltooid')
                    ->body($body)
                    ->warning($result->errors !== [])
                    ->persistent($result->errors !== [])
                    ->success($result->errors === [])
                    ->send();
            });
    }

    /**
     * @return array<int, mixed>
     */
    protected static function getFormSchema(): array
    {
        return [
            FileUpload::make('file')
                ->label('Excel-bestand')
                ->acceptedFileTypes([
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-excel',
                    'text/csv',
                    'text/plain',
                ])
                ->disk('local')
                ->directory('sync-uploads')
                ->visibility('private')
                ->maxSize(8192)
                ->required(),

            Select::make('user_id')
                ->label('Gebruiker')
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->preload()
                ->required()
                ->default(auth()->id())
                ->hidden(),

            Toggle::make('delete_missing')
                ->label('Verwijder registraties die niet in het bestand staan')
                ->helperText('Let op: registraties van deze gebruiker die niet in het bestand staan, worden definitief verwijderd.')
                ->default(false)
                ->required(),
        ];
    }
}
