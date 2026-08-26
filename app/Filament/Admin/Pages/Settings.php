<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.settings';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return 'Instellingen';
    }

    public function getTitle(): string
    {
        return 'Instellingen';
    }

    public function mount(): void
    {
        $user = auth()->user();

        $this->form->fill($user->only(['name', 'email', 'accent_color']));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Opslaan')
                ->icon('heroicon-o-check')
                ->action('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function form(Schema $schema): Schema
    {
        $accentColor = auth()->user()->accent_color ?? 'amber';

        return $schema
            ->model(auth()->user())
            ->operation('edit')
            ->statePath('data')
            ->components([
                Section::make('Account')
                    ->description('Beheer je accountgegevens.')
                    ->schema([
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

                        TextInput::make('password')
                            ->label('Nieuw wachtwoord')
                            ->password()
                            ->revealable()
                            ->rule('min:8')
                            ->nullable()
                            ->same('password_confirmation')
                            ->hintIcon('heroicon-m-information-circle', tooltip: 'Leeg laten om je huidige wachtwoord te behouden · minimaal 8 tekens'),

                        TextInput::make('password_confirmation')
                            ->label('Nieuw wachtwoord bevestigen')
                            ->password()
                            ->revealable()
                            ->dehydrated(false),
                    ]),

                Section::make('Accentkleur')
                    ->description('Kies je persoonlijke accentkleur voor de applicatie.')
                    ->schema([
                        View::make('filament.components.accent-color-picker')
                            ->data(['accentColor' => $accentColor]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        /** @var User $user */
        $user = auth()->user();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        unset($data['password_confirmation']);

        $user->update($data);

        $this->dispatch('accent-color-changed');

        Notification::make()
            ->title('Instellingen opgeslagen')
            ->success()
            ->send();
    }
}
