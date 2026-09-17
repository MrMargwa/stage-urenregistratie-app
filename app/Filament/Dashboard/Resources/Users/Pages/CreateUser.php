<?php

namespace App\Filament\Dashboard\Resources\Users\Pages;

use App\Filament\Dashboard\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
