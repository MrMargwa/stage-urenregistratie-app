<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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

        $this->form->fill($user->only([
            'name',
            'email',
            'target_hours',
            'default_start_time',
            'default_end_time',
            'default_break_minutes',
        ]));
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
                            ->hintIcon('heroicon-m-information-circle', tooltip: 'Leeg laten om je huidige wachtwoord te behouden · minimaal 8 tekens'),
                    ]),

                Section::make('Stage')
                    ->description('Stel het totale aantal uren in dat je moet lopen')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        TextInput::make('target_hours')
                            ->label('Totaal te lopen uren')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(9999)
                            ->placeholder('bijv. 500')
                            ->helperText('Het totale aantal stage-uren dat je moet voltooien'),
                    ]),

                Section::make('Standaard werktijden')
                    ->description('Nieuwe registraties worden met deze tijden voorgevuld. Je kunt ze per dag altijd aanpassen.')
                    ->icon('heroicon-o-clock')
                    ->columns(3)
                    ->schema([
                        TimePicker::make('default_start_time')
                            ->label('Begintijd')
                            ->seconds(false)
                            ->displayFormat('H:i')
                            ->format('H:i')
                            ->placeholder(User::DEFAULT_START_TIME),

                        TimePicker::make('default_end_time')
                            ->label('Eindtijd')
                            ->seconds(false)
                            ->displayFormat('H:i')
                            ->format('H:i')
                            ->placeholder(User::DEFAULT_END_TIME)
                            ->afterOrEqual('default_start_time'),

                        TextInput::make('default_break_minutes')
                            ->label('Pauze (minuten)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1440)
                            ->placeholder((string) User::DEFAULT_BREAK_MINUTES),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        /** @var User $user */
        $user = auth()->user();

        $clean = [
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'target_hours' => $data['target_hours'] ?? null,
            'default_start_time' => $data['default_start_time'] ?? null,
            'default_end_time' => $data['default_end_time'] ?? null,
            'default_break_minutes' => $data['default_break_minutes'] ?? null,
        ];

        if (filled($data['password'] ?? null)) {
            $clean['password'] = $data['password'];
        }

        $user->update($clean);

        Notification::make()
            ->title('Instellingen opgeslagen')
            ->success()
            ->send();
    }
}
