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

#[Fillable(['name', 'email', 'password', 'role', 'target_hours', 'ui_preferences'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUiPreferences, Notifiable;

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
            'ui_preferences' => 'array',
        ];
    }
}
