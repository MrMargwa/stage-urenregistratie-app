<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@admin.com';

    public const ADMIN_DEFAULT_PASSWORD = 'Admin1!23';

    /**
     * Zorgt dat er altijd precies één admin-account bestaat.
     *
     * De seeder is idempotent: als de admin er al is (bijvoorbeeld met een
     * online gewijzigd wachtwoord), laat hij die gegevens met rust. Zo kun je
     * het wachtwoord na de eerste login één keer aanpassen en blijft dat
     * bewaard bij volgende deploys.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => self::ADMIN_EMAIL],
            [
                'name' => 'Admin',
                'password' => self::ADMIN_DEFAULT_PASSWORD,
                'role' => Role::Admin,
            ]
        );
    }
}
