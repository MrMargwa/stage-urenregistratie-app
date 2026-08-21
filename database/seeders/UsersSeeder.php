<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         User::create([
            "name"=>"Admin",
            "email"=>"admin@example.com",
            "password"=>Hash::make('Admin1!23'),
        ]);
         User::create([
            "name"=>"Justin Haringa",
            "email"=>"j.haringa@agiliq.nl",
            "password"=>Hash::make('password1!23'),
        ]);
    }
}
