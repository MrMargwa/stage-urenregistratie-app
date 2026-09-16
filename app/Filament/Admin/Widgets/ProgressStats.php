<?php

namespace App\Filament\Admin\Widgets;

use App\Helpers\DurationHelper;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProgressStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $target = (int) $user->target_hours;
        $totalMinutes = $user->totalLoggedMinutes();

        $formattedTotal = DurationHelper::formatHours($totalMinutes);

        if ($target <= 0) {
            return [
                Stat::make('Doel', 'Nog niet ingesteld')
                    ->description('Stel je totale stage-uren in via je instellingen')
                    ->descriptionIcon(Heroicon::OutlinedArrowTrendingUp, IconPosition::Before)
                    ->icon(Heroicon::OutlinedFlag)
                    ->color('gray')
                    ->url(fn (): string => route('filament.dashboard.pages.settings')),
            ];
        }

        $totalHours = $totalMinutes / 60;
        $percentage = min(($totalHours / $target) * 100, 100);
        $remainingMinutes = $target > $totalHours
            ? (int) round(($target - $totalHours) * 60)
            : 0;
        $done = $remainingMinutes <= 0;

        $percentageLabel = number_format($percentage, 1, ',', '.').'%';

        $gelopenStat = Stat::make('Gelopen', $formattedTotal)
            ->icon(Heroicon::OutlinedClock)
            ->color('primary');

        if ($totalMinutes <= 0) {
            $gelopenStat
                ->description('Vul hier je stage-uren in om te beginnen')
                ->descriptionIcon(Heroicon::OutlinedArrowTrendingUp, IconPosition::After)
                ->url(fn (): string => route('filament.dashboard.resources.time-entries.create'));
        } else {
            $gelopenStat->description($percentageLabel.' voltooid');
        }

        return [
            $gelopenStat,
            Stat::make('Doel', $target.' uur')
                ->description('totale stage-uren')
                ->icon(Heroicon::OutlinedFlag)
                ->color('gray'),
            Stat::make($done ? 'Doel gehaald' : 'Nog te gaan', $done ? 'Voltooid' : DurationHelper::formatHours($remainingMinutes))
                ->description($done ? 'Goed bezig!' : 'resterend')
                ->icon($done ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedArrowTrendingUp)
                ->color($done ? 'success' : 'warning'),
        ];
    }
}
