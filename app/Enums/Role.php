<?php

namespace App\Enums;

enum Role: string
{
    case Student = 'student';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Admin => 'Admin',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    /**
     * Option-map voor selects/filters: ['student' => 'Student', ...].
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->toArray();
    }
}
