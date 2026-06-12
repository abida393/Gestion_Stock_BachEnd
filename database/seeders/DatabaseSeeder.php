<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            CategorySeeder::class,
            FournisseurSeeder::class,
            ProductSeeder::class,
            MouvementStockSeeder::class,
            AlerteSeeder::class,
            PrevisionSeeder::class,
            RapportSeeder::class,
            AuditLogSeeder::class,
            // UserSeeder::class, // Excluded as requested
        ]);
    }
}
