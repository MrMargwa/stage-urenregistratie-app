<?php

namespace App\Filament\Dashboard\Resources\Users\Schemas;

use App\Enums\Role;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
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
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Dit e-mailadres is al in gebruik.',
                    ]),

                Select::make('role')
                    ->label('Rol')
                    ->options(Role::options())
                    ->required()
                    ->default(Role::Student)
                    ->disabled(fn (Component $component): bool => $component->getRecord() instanceof User
                        && $component->getRecord()->isLastAdmin()),

                TextInput::make('password')
                    ->label('Wachtwoord')
                    ->password()
                    ->revealable()
                    ->rule('min:8')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state): bool => filled($state))
                    ->same('password_confirmation')
                    ->hintIcon('heroicon-m-information-circle', tooltip: 'Minimaal 8 tekens')
                    ->validationMessages([
                        'min' => 'Het wachtwoord moet minimaal 8 tekens bevatten.',
                        'same' => 'De wachtwoorden komen niet overeen.',
                    ]),

                TextInput::make('password_confirmation')
                    ->label('Wachtwoord bevestigen')
                    ->password()
                    ->revealable()
                    ->dehydrated(false)
                    ->required(fn (string $operation): bool => $operation === 'create'),
            ]);
    }
}
