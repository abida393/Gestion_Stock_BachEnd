<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Alerte;
use App\Models\Produit;
use Faker\Factory as Faker;

class AlerteSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');
        $produits = Produit::all();

        if ($produits->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 50; $i++) {
            $produit = $produits->random();
            Alerte::create([
                'produit_id' => $produit->id,
                'seuil' => $produit->seuil_min,
                'type' => $faker->randomElement(['stock_bas', 'rupture', 'anomalie']),
                'message' => $faker->sentence,
                'confiance' => $faker->randomFloat(2, 0, 1),
                'est_active' => $faker->boolean(80),
                'declenche_le' => $faker->dateTimeBetween('-1 months', 'now'),
                'resolu_le' => $faker->boolean(30) ? $faker->dateTimeBetween('-1 weeks', 'now') : null,
            ]);
        }
    }
}
