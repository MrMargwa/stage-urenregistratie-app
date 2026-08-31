<?php

namespace App\Models;

use Database\Factories\TimeEntryFactory;
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
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function duration(): Attribute
    {
        return Attribute::get(function () {
            $minutes = (int) round($this->start_time->diffInMinutes($this->end_time));

            if ($minutes < 0) {
                $minutes += 1440;
            }

            return max(0, $minutes - $this->break_minutes);
        });
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

                $entry->assertNoOverlap();
            }
        });
    }

    /**
     * Controleert of deze entry overlapt met een andere entry van dezelfde
     * gebruiker op dezelfde dag (exclusief de huidige rij bij een update).
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
                $q->whereBetween('start_time', [$start, $end])
                    ->orWhereBetween('end_time', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end): void {
                        $q->where('start_time', '<=', $start)
                            ->where('end_time', '>=', $end);
                    });
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_time' => 'Deze registratie overlapt met een bestaande registratie op deze dag.',
            ]);
        }
    }
}
