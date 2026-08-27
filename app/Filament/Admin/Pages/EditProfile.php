<?php

namespace App\Filament\Admin\Pages;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    // public function form(Schema $schema): Schema
    // {
    //     return $schema
    //         ->components([
    //             $this->getNameFormComponent(),
    //             $this->getEmailFormComponent(),
    //             $this->getPasswordFormComponent(),
    //             $this->getPasswordConfirmationFormComponent(),
    //             $this->getCurrentPasswordFormComponent(),
    //             Section::make('Stage')
    //                 ->description('Stel het totale aantal uren in dat je moet lopen')
    //                 ->icon('heroicon-o-academic-cap')
    //                 ->schema([
    //                     TextInput::make('target_hours')
    //                         ->label('Totaal te lopen uren')
    //                         ->numeric()
    //                         ->minValue(1)
    //                         ->maxValue(9999)
    //                         ->placeholder('bijv. 500')
    //                         ->helperText('Het totale aantal stage-uren dat je moet voltooien'),
    //                 ]),
    //         ]);
    // }
}
