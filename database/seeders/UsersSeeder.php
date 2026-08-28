<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = env('SEED_ADMIN_PASSWORD', 'Welkom1!23');
        $userPassword = env('SEED_USER_PASSWORD', 'Welkom1!23');

        $this->upsertUser([
            'email' => 'admin@admin.com',
            'name' => 'Admin',
            'password' => Hash::make($adminPassword),
            'role' => Role::Admin,
        ]);

        $this->upsertUser([
            'email' => 'testaccount01@example.com',
            'name' => 'Test Account 01',
            'password' => Hash::make($userPassword),
            'role' => Role::User,
        ]);
    }

    /**
     * @param  array{email: string, name: string, password: string, role: Role}  $attributes
     */
    private function upsertUser(array $attributes): void
    {
        $email = $attributes['email'];

        unset($attributes['email']);

        User::updateOrCreate(['email' => $email], $attributes);
    }
}
