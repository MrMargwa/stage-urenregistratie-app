<?php

namespace App\Filament\Admin\Resources\TimeEntries\Tables;

use App\Filament\Admin\Resources\TimeEntries\TimeEntryResource;
use App\Helpers\DurationHelper;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TimeEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Gebruiker')
                    ->sortable()
                    ->hidden(),

                TextColumn::make('date')
                    ->label('Datum')
                    ->date('d-m-Y')
                    ->sortable(),

                TextColumn::make('start_time')
                    ->label('Begintijd')
                    ->time('H:i'),

                TextColumn::make('end_time')
                    ->label('Eindtijd')
                    ->time('H:i'),

                TextColumn::make('break_minutes')
                    ->label('Pauze (minuten)'),

                TextColumn::make('description')
                    ->label('Beschrijving'),

                TextColumn::make('duration')
                    ->label('Duur')
                    ->formatStateUsing(fn ($state) => DurationHelper::formatMinutes($state)),
            ])
            ->filters([
                SelectFilter::make('week')
                    ->label('Weekstaat')
                    ->options(fn (): array => self::weekOptions())
                    ->query(function (Builder $query, array $data): void {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return;
                        }

                        $start = Carbon::parse($value)->startOfWeek();
                        $end = $start->copy()->endOfWeek();

                        $query->whereBetween('date', [$start, $end]);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Bouwt de lijst van beschikbare weken voor de filter, gebaseerd op de
     * datums die in de eigen registraties van de gebruiker voorkomen
     * (van oudste tot nieuwste week). Iedereen ziet alleen eigen uren.
     *
     * @return array<string, string>
     */
    private static function weekOptions(): array
    {
        $dates = TimeEntry::query()
            ->where('user_id', auth()->id())
            ->select('date')
            ->distinct()
            ->orderBy('date')
            ->get()
            ->pluck('date');

        return $dates->mapWithKeys(function ($date): array {
            $carbon = Carbon::parse($date);
            $start = $carbon->copy()->startOfWeek();
            $key = $start->toDateString();
            $label = 'Week '.$start->isoWeek().' · '.$start->format('d M Y');

            return [$key => $label];
        })->unique()->toArray();
    }
}
