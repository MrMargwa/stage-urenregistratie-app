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
        User::create([
            'email' => 'admin@admin.com',
            'name' => 'Admin',
            'password' => Hash::make('Admin1!23'),
            'role' => Role::Admin,
        ]);

        User::create([
            'email' => 'testaccount01@example.com',
            'name' => 'Test Account 01',
            'password' => Hash::make('Welkom1!23'),
            'role' => Role::User,
        ]);
    }
}
