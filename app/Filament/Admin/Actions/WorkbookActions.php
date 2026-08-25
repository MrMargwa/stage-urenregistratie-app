<?php

namespace App\Filament\Admin\Actions;

use App\Models\User;
use App\Services\WorkbookService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
                'Je koppelt één keer je eigen stage-urenwerkblad. Daarna wordt het bestand '
                .'automatisch bijgewerkt zodra je een uur toevoegt, aanpast of verwijdert. '
                .'Je kunt het werkblad op elk moment downloaden.'
            )
            ->modalSubmitActionLabel('Koppelen en genereren')
            ->action(function (): void {
                /** @var User $user */
                $user = auth()->user();

                app(WorkbookService::class)->link($user);

                Notification::make()
                    ->title('Excel-werkblad gekoppeld')
                    ->body('Je werkblad is gegenereerd en wordt vanaf nu automatisch bijgewerkt.')
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
