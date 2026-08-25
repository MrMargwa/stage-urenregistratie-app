<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
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

    public function duration(): Attribute
    {
        return Attribute::get(function () {
            $minutes = (int) round($this->start_time->diffInMinutes($this->end_time));

            if ($minutes < 0) {
                $minutes += 1440;
            }

            return max(0, $minutes - $this->break_minutes);
        });
    }
}
