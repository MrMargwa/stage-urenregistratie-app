<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Form;
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
        $this->form->fill(auth()->user()->only(['name', 'email', 'theme_mode', 'accent_color']));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->model(auth()->user())
            ->operation('edit')
            ->statePath('data')
            ->components([
                Form::make([
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

                    Section::make('Weergave')
                        ->description('Pas het thema en de accentkleur van de applicatie aan.')
                        ->schema([
                            ToggleButtons::make('theme_mode')
                                ->label('Thema')
                                ->options([
                                    'dark' => 'Donker',
                                    'light' => 'Licht',
                                    'system' => 'Systeem',
                                ])
                                ->icons([
                                    'dark' => 'heroicon-o-moon',
                                    'light' => 'heroicon-o-sun',
                                    'system' => 'heroicon-o-computer-desktop',
                                ])
                                ->inline()
                                ->required(),

                            ToggleButtons::make('accent_color')
                                ->label('Accentkleur')
                                ->options(self::accentOptions())
                                ->columns(5)
                                ->required(),
                        ]),
                ])
                    ->livewireSubmitHandler('save')
                    ->footerActions([
                        Action::make('save')
                            ->label('Wijzigingen opslaan')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
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

        $this->dispatch('settings-applied', theme: $user->theme_mode);

        Notification::make()
            ->title('Instellingen opgeslagen')
            ->success()
            ->send();
    }

    /** @return array<string, string> */
    public static function accentOptions(): array
    {
        $labels = [
            'red' => 'Rood',
            'orange' => 'Oranje',
            'yellow' => 'Geel',
            'lime' => 'Limoen',
            'green' => 'Groen',
            'emerald' => 'Emerald',
            'teal' => 'Teal',
            'cyan' => 'Cyaan',
            'sky' => 'Hemelsblauw',
            'blue' => 'Blauw',
            'indigo' => 'Indigo',
            'violet' => 'Violet',
            'purple' => 'Paars',
            'fuchsia' => 'Fuchsia',
            'pink' => 'Roze',
        ];

        return array_intersect_key($labels, User::ACCENT_COLORS);
    }
}
