<?php

namespace App\Enums;

enum Role: string
{
    case Student = 'student';
    case Gebruiker = 'gebruiker';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Gebruiker => 'Gebruiker',
            self::Admin => 'Admin',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}
