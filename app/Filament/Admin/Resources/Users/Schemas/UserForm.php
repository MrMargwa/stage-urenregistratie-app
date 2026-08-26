<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Enums\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Naam')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('E-mailadres')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Select::make('role')
                    ->label('Rol')
                    ->options(fn () => collect(Role::cases())
                        ->mapWithKeys(fn (Role $role) => [$role->value => $role->label()])
                        ->toArray())
                    ->required()
                    ->default(Role::User),

                TextInput::make('password')
                    ->label('Wachtwoord')
                    ->password()
                    ->revealable()
                    ->rule('min:8')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state): bool => filled($state))
                    ->same('password_confirmation')
                    ->hintIcon('heroicon-m-information-circle', tooltip: 'Minimaal 8 tekens'),

                TextInput::make('password_confirmation')
                    ->label('Wachtwoord bevestigen')
                    ->password()
                    ->revealable()
                    ->dehydrated(false)
                    ->required(fn (string $operation): bool => $operation === 'create'),
            ]);
    }
}
