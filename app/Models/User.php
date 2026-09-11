<?php

namespace App\Models;

use Andreia\FilamentUiSwitcher\Models\Traits\HasUiPreferences;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'target_hours', 'default_start_time', 'default_end_time', 'default_break_minutes', 'ui_preferences'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUiPreferences, Notifiable;

    public const DEFAULT_START_TIME = '08:30';

    public const DEFAULT_END_TIME = '17:00';

    public const DEFAULT_BREAK_MINUTES = 30;

    /**
     * Standaard begintijd voor nieuwe registraties: de eigen instelling,
     * of anders een verstandige terugvalwaarde.
     */
    public function defaultStartTime(): string
    {
        return $this->default_start_time?->format('H:i') ?? self::DEFAULT_START_TIME;
    }

    public function defaultEndTime(): string
    {
        return $this->default_end_time?->format('H:i') ?? self::DEFAULT_END_TIME;
    }

    public function defaultBreakMinutes(): int
    {
        return $this->default_break_minutes ?? self::DEFAULT_BREAK_MINUTES;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isLastAdmin(): bool
    {
        return $this->isAdmin()
            && self::where('role', Role::Admin)
                ->where('id', '!=', $this->id)
                ->doesntExist();
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Verwijdert de stage-uren van een gebruiker zodra die gebruiker wordt
     * verwijderd. Dit is de 'cascade delete' op applicatieniveau, als extra
     * bescherming naast de FK met `cascadeOnDelete` in de database.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $user->timeEntries()->delete();
        });
    }

    public function totalLoggedMinutes(): int
    {
        return (int) $this->timeEntries()->sum('duration_minutes');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'default_start_time' => 'datetime:H:i',
            'default_end_time' => 'datetime:H:i',
            'default_break_minutes' => 'integer',
            'ui_preferences' => 'array',
        ];
    }
}
