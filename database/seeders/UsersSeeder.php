<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Test Account 01',
            'email' => 'testaccount01@admin.com',
            'password' => Hash::make('Welkom1!23'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Student 01',
            'email' => 'student01@example.com',
            'password' => Hash::make('Welkom1!23'),
            'role' => 'student',
        ]);

        User::create([
            'name' => 'Justin Haringa',
            'email' => '253258@student.firda.nl',
            'password' => Hash::make('Welkom1!23'),
            'role' => 'admin',
        ]);
    }
}
