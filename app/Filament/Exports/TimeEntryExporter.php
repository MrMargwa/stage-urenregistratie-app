<?php

namespace App\Filament\Exports;

use App\Helpers\DurationHelper;
use App\Models\TimeEntry;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;

class TimeEntryExporter extends Exporter
{
    protected static ?string $model = TimeEntry::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('week_number')
                ->label('Week')
                ->state(fn (TimeEntry $record): string => 'Week '.$record->date->isoWeek()),

            ExportColumn::make('date')
                ->label('Datum')
                ->formatStateUsing(fn ($state) => $state->format('d-m-Y')),

            ExportColumn::make('start_time')
                ->label('Begintijd')
                ->formatStateUsing(fn ($state) => $state->format('H:i')),

            ExportColumn::make('end_time')
                ->label('Eindtijd')
                ->formatStateUsing(fn ($state) => $state->format('H:i')),

            ExportColumn::make('break_minutes')
                ->label('Pauze (minuten)'),

            ExportColumn::make('description')
                ->label('Beschrijving'),

            ExportColumn::make('duration')
                ->label('Duur')
                ->formatStateUsing(fn ($state) => DurationHelper::formatMinutes($state)),
        ];
    }

    /**
     * Haalt de gekozen UI-kleur van de gebruiker op.
     */
    protected function getUiColor(): string
    {
        return $this->export->user
            ->ui_preferences['ui.color']
            ?? '#6366f1';
    }

    /**
     * Zet een hex-kleur om naar RGB.
     */
    protected function getUiColorRgb(): array
    {
        $hex = ltrim($this->getUiColor(), '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Styling voor de kopregel.
     */
    public function getXlsxHeaderCellStyle(): ?Style
    {
        [$red, $green, $blue] = $this->getUiColorRgb();

        return (new Style)
            ->setFontBold()
            ->setFontColor(Color::BLACK)
            ->setBackgroundColor(
                Color::rgb($red, $green, $blue)
            )
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }

    /**
     * Stelt de breedte van de Excel-kolommen in.
     */
    public function getXlsxWriterOptions(): ?Options
    {
        $options = new Options;

        $options->setColumnWidth(10, 1); // Week
        $options->setColumnWidth(12, 2); // Datum
        $options->setColumnWidth(10, 3); // Begintijd
        $options->setColumnWidth(10, 4); // Eindtijd
        $options->setColumnWidth(18, 5); // Pauze (minuten)
        $options->setColumnWidth(40, 6); // Beschrijving
        $options->setColumnWidth(12, 7); // Duur

        return $options;
    }

    /**
     * Styling voor alle data-rijen.
     */
    public function getXlsxCellStyle(): ?Style
    {
        [$red, $green, $blue] = $this->getUiColorRgb();

        return (new Style)
            ->setFontColor(
                Color::rgb($red, $green, $blue)
            )
            ->setShouldWrapText(false)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Je export is klaar, '.$export->successful_rows.' rijen geëxporteerd.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.$failedRowsCount.' rijen zijn mislukt.';
        }

        return $body;
    }
}
