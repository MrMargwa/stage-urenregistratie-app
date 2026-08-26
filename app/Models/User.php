<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'theme_mode', 'accent_color', 'workbook_linked_at', 'target_hours'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const THEME_MODES = ['dark', 'light', 'system'];

    /**
     * @var array<string, array<string, array<int|string, string|int>|string>>
     */
    public const ACCENT_COLORS = [
        'red' => Color::Red,
        'orange' => Color::Orange,
        'yellow' => Color::Yellow,
        'lime' => Color::Lime,
        'green' => Color::Green,
        'emerald' => Color::Emerald,
        'teal' => Color::Teal,
        'cyan' => Color::Cyan,
        'sky' => Color::Sky,
        'blue' => Color::Blue,
        'indigo' => Color::Indigo,
        'violet' => Color::Violet,
        'purple' => Color::Purple,
        'fuchsia' => Color::Fuchsia,
        'pink' => Color::Pink,
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function hasLinkedWorkbook(): bool
    {
        return $this->workbook_linked_at !== null;
    }

    /**
     * @return array<string, array<int|string, string|int>|string>
     */
    public function primaryColor(): array
    {
        return self::ACCENT_COLORS[$this->accent_color] ?? Color::Cyan;
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function totalLoggedMinutes(): int
    {
        return (int) $this->timeEntries->sum('duration');
    }

    public function totalLoggedHoursFormatted(): string
    {
        $total = $this->totalLoggedMinutes();

        return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
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
            'workbook_linked_at' => 'datetime',
        ];
    }
}
