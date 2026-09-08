<?php

namespace App\Models;

use App\Helpers\DurationHelper;
use Database\Factories\TimeEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class TimeEntry extends Model
{
    /** @use HasFactory<TimeEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'break_minutes',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'break_minutes' => 'integer',
        'duration_minutes' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Beperkt de query tot tijdregistraties van de opgegeven gebruiker.
     *
     * Dit is de enige plek waar ownership-scoping wordt gedefinieerd, zodat
     * elke plek die eigen uren toont (resource, dashboard, filters) dezelfde,
     * consistente scope gebruikt.
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    protected function duration(): Attribute
    {
        return Attribute::get(fn (): int => $this->computeDuration());
    }

    /**
     * Berekent de netto duur (eindtijd − begintijd − pauze) in minuten met
     * dezelfde formule die ook in migraties / DurationHelper wordt gebruikt.
     */
    private function computeDuration(): int
    {
        if (! $this->start_time || ! $this->end_time) {
            return 0;
        }

        return DurationHelper::toMinutes(
            $this->start_time->format('H:i'),
            $this->end_time->format('H:i'),
            (int) $this->break_minutes,
        );
    }

    public static function boot(): void
    {
        parent::boot();

        static::saving(function (TimeEntry $entry): void {
            if ($entry->start_time && $entry->end_time) {
                $start = $entry->start_time;
                $end = $entry->end_time;

                if ($end->lt($start)) {
                    throw ValidationException::withMessages([
                        'end_time' => 'De eindtijd kan niet voor de begintijd liggen.',
                    ]);
                }

                $entry->duration_minutes = $entry->computeDuration();
                $entry->assertNoOverlap();
            }
        });
    }

    /**
     * Controleert of deze entry overlapt met een andere entry van dezelfde
     * gebruiker op dezelfde dag (exclusief de huidige rij bij een update).
     *
     * Aaneengesloten registraties (de ene eindigt waar de andere begint)
     * worden NIET als overlap beschouwd.
     *
     * @throws ValidationException
     */
    public function assertNoOverlap(): void
    {
        if (! $this->user_id || ! $this->date || ! $this->start_time || ! $this->end_time) {
            return;
        }

        $start = $this->start_time->format('H:i:s');
        $end = $this->end_time->format('H:i:s');

        $overlap = TimeEntry::query()
            ->where('user_id', $this->user_id)
            ->whereDate('date', $this->date->toDateString())
            ->where('id', '!=', $this->id)
            ->where(function ($q) use ($start, $end): void {
                $q->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_time' => 'Deze registratie overlapt met een bestaande registratie op deze dag.',
            ]);
        }
    }
}
