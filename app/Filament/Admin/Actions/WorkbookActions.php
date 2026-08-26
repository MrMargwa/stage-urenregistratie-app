<?php

namespace App\Filament\Admin\Actions;

use App\Models\User;
use App\Services\WorkbookService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class WorkbookActions
{
    /**
     * Header-actions voor het persoonlijke Excel-werkblad.
     *
     * @return array<int, Action|ActionGroup>
     */
    public static function forHeader(): array
    {
        $workbooks = app(WorkbookService::class);
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        if (! $workbooks->isLinked($user)) {
            return [
                self::linkAction(),
            ];
        }

        return [
            ActionGroup::make([
                self::downloadAction(),
                self::unlinkAction(),
            ])
                ->label('Mijn Excel-werkblad')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->button(),
        ];
    }

    protected static function linkAction(): Action
    {
        return Action::make('link_workbook')
            ->label('Excel koppelen')
            ->icon('heroicon-o-link')
            ->color('gray')
            ->modalHeading('Persoonlijk Excel-werkblad koppelen')
            ->modalDescription(
                'Geef het volledige pad op naar het Excel-bestand op je computer waar de uren in geschreven moeten worden. '
                .'Bijvoorbeeld: C:\Users\Naam\Documents\stage-uren.xlsx'
            )
            ->modalSubmitActionLabel('Koppelen en genereren')
            ->form([
                TextInput::make('workbook_path')
                    ->label('Pad naar Excel-bestand')
                    ->placeholder('C:\Users\Naam\Documents\stage-uren.xlsx')
                    ->required()
                    ->helperText('Het volledige absolute pad naar het bestand op je PC.'),
            ])
            ->action(function (array $data): void {
                /** @var User $user */
                $user = auth()->user();

                $path = $data['workbook_path'];

                if (! file_exists(dirname($path))) {
                    Notification::make()
                        ->title('Pad niet gevonden')
                        ->body('De map "'.dirname($path).'" bestaat niet. Controleer het pad.')
                        ->danger()
                        ->send();

                    return;
                }

                app(WorkbookService::class)->link($user, $path);

                Notification::make()
                    ->title('Excel-werkblad gekoppeld')
                    ->body('Je werkblad is gegenereerd op '.$path.' en wordt vanaf nu automatisch bijgewerkt.')
                    ->success()
                    ->send();
            });
    }

    protected static function downloadAction(): Action
    {
        return Action::make('download_workbook')
            ->label('Downloaden (.xlsx)')
            ->icon('heroicon-o-arrow-down-tray')
            ->url(route('workbook.download'))
            ->openUrlInNewTab();
    }

    protected static function unlinkAction(): Action
    {
        return Action::make('unlink_workbook')
            ->label('Werkblad ontkoppelen')
            ->icon('heroicon-o-link-slash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Excel-werkblad ontkoppelen?')
            ->modalDescription(
                'Je uren blijven gewoon in de app staan, maar het werkblad wordt niet langer '
                .'automatisch bijgewerkt. Je kunt later altijd weer een nieuw werkblad koppelen.'
            )
            ->modalSubmitActionLabel('Ontkoppelen')
            ->action(function (): void {
                /** @var User $user */
                $user = auth()->user();

                app(WorkbookService::class)->unlink($user);

                Notification::make()
                    ->title('Excel-werkblad ontkoppeld')
                    ->success()
                    ->send();
            });
    }
}
