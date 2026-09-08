<?php

namespace App\Models;

use Andreia\FilamentUiSwitcher\Models\Traits\HasUiPreferences;
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

#[Fillable(['name', 'email', 'password', 'role', 'theme_mode', 'target_hours', 'ui_preferences'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUiPreferences, Notifiable;

    public const THEME_MODES = ['dark', 'light', 'system'];

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
     * verwijderd. Dit is de 'cascade delete' op applicatieniveau: het werkt op
     * alle ondersteunde databases (SQLite in tests, PostgreSQL in productie)
     * zonder dat de FK-constraint van time_entries hoeft te worden aangepast.
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

    public function totalLoggedHoursFormatted(): string
    {
        return DurationHelper::formatMinutes($this->totalLoggedMinutes());
    }

    /**
     * Bepaal de achtergrond- en tekstkleur voor de Excel-header op basis van de
     * actieve filament-palette van de ingelogde gebruiker.
     *
     * @return array{bg: string, font: string}
     */
    public function exportColors(): array
    {
        $bgHex = 'F59E0B';

        try {
            $palette = config('filament-palette.palette.'.app('filament.palette')->get(), []);

            $primary = $palette['primary'] ?? Color::Amber;

            foreach ([500, 600, 400] as $shade) {
                if (isset($primary[$shade])) {
                    $bgHex = ltrim((string) $primary[$shade], '#');
                    break;
                }
            }
        } catch (\Throwable) {
            // fall back to amber
        }

        [$r, $g, $b] = array_map('hexdec', str_split($bgHex, 2));

        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return [
            'bg' => strtoupper($bgHex),
            'font' => $luminance > 0.6 ? '000000' : 'FFFFFF',
        ];
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
