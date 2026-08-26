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
            }
        });
    }
}
