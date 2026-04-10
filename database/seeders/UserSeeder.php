<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@stockmanager.com'],
            [
                'name' => 'Adrien Admin',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('admin');

        // User
        $user = User::firstOrCreate(
            ['email' => 'user@stockmanager.com'],
            [
                'name' => 'Simple User',
                'password' => Hash::make('password'),
            ]
        );
        $user->assignRole('user');
    }
}
