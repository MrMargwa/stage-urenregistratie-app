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
     *
     * Het e-mailadres en wachtwoord kunnen via SEED_ADMIN_EMAIL /
     * SEED_ADMIN_PASSWORD worden ingesteld (config/seeding.php); zonder die
     * variabelen vallen we terug op de standaardwaarden hieronder.
     */
    public function run(): void
    {
        $email = (string) config('seeding.admin_email', self::ADMIN_EMAIL);
        $password = (string) config('seeding.admin_password', self::ADMIN_DEFAULT_PASSWORD);

        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'password' => $password,
                'role' => Role::Admin,
            ]
        );
    }
}
