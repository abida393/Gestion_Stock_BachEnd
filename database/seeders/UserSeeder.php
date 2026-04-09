<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('nom', 'Admin')->first();
        $managerRole = Role::where('nom', 'Utilisateur')->first();

        // Admin
        User::updateOrCreate(
            ['email' => 'admin@stockmanager.com'],
            [
                'name' => 'Adrien Admin',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
            ]
        );

        // Manager
        User::updateOrCreate(
            ['email' => 'manager@stockmanager.com'],
            [
                'name' => 'Marc Manager',
                'password' => Hash::make('password'),
                'role_id' => $managerRole->id,
            ]
        );
    }
}
