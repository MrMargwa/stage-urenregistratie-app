<?php

namespace App\Models;

use App\Enums\Role;
use App\Helpers\DurationHelper;
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

#[Fillable(['name', 'email', 'password', 'role', 'theme_mode', 'accent_color', 'workbook_linked_at', 'workbook_path', 'target_hours'])]
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
        'amber' => Color::Amber,
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
        return self::ACCENT_COLORS[$this->accent_color] ?? Color::Amber;
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
        return DurationHelper::formatMinutes($this->totalLoggedMinutes());
    }

    public function exportColors(): array
    {
        $colors = [
            'red' => ['bg' => 'FF4444', 'font' => 'FFFFFF'],
            'orange' => ['bg' => 'FF8C00', 'font' => 'FFFFFF'],
            'amber' => ['bg' => 'F59E0B', 'font' => 'FFFFFF'],
            'yellow' => ['bg' => 'FFD700', 'font' => '000000'],
            'lime' => ['bg' => '9ACD32', 'font' => '000000'],
            'green' => ['bg' => '22C55E', 'font' => 'FFFFFF'],
            'emerald' => ['bg' => '10B981', 'font' => 'FFFFFF'],
            'teal' => ['bg' => '14B8A6', 'font' => 'FFFFFF'],
            'cyan' => ['bg' => '06B6D4', 'font' => 'FFFFFF'],
            'sky' => ['bg' => '0EA5E9', 'font' => 'FFFFFF'],
            'blue' => ['bg' => '3B82F6', 'font' => 'FFFFFF'],
            'indigo' => ['bg' => '6366F1', 'font' => 'FFFFFF'],
            'violet' => ['bg' => '8B5CF6', 'font' => 'FFFFFF'],
            'purple' => ['bg' => 'A855F7', 'font' => 'FFFFFF'],
            'fuchsia' => ['bg' => 'D946EF', 'font' => 'FFFFFF'],
            'pink' => ['bg' => 'EC4899', 'font' => 'FFFFFF'],
        ];

        return $colors[$this->accent_color] ?? $colors['amber'];
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


