<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Produit;
use Faker\Factory as Faker;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');
        $admin = User::first();
        $produits = Produit::all();

        if (!$admin || $produits->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 100; $i++) {
            $produit = $produits->random();
            AuditLog::create([
                'user_id' => $admin->id,
                'auditable_type' => Produit::class,
                'auditable_id' => $produit->id,
                'event' => $faker->randomElement(['created', 'updated', 'deleted']),
                'old_values' => ['quantite' => $faker->numberBetween(0, 100)],
                'new_values' => ['quantite' => $faker->numberBetween(0, 100)],
                'ip_address' => $faker->ipv4,
                'user_agent' => $faker->userAgent,
            ]);
        }
    }
}
