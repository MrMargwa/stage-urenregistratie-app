<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Admin1!23'),
                'role' => User::ROLE_ADMIN,
            ]
        );

        User::updateOrCreate(
            ['email' => 'j.haringa@agiliq.nl'],
            [
                'name' => 'Justin Haringa',
                'password' => Hash::make('password1!23'),
                'role' => User::ROLE_USER,
            ]
        );

    }
}
