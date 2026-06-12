<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MouvementStock;
use App\Models\Produit;
use App\Models\User;
use Faker\Factory as Faker;

class MouvementStockSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');
        $produits = Produit::all();
        $admin = User::first();

        if ($produits->isEmpty() || !$admin) {
            return;
        }

        for ($i = 0; $i < 500; $i++) {
            $produit = $produits->random();
            MouvementStock::create([
                'produit_id' => $produit->id,
                'utilisateur_id' => $admin->id,
                'quantite' => $faker->numberBetween(1, 100),
                'stock_apres' => 0, // Ignored or updated by observers
                'note' => $faker->sentence,
                'date_mouvement' => $faker->dateTimeBetween('-1 years', 'now'),
                'type' => $faker->randomElement(['entree', 'sortie']),
            ]);
        }
    }
}
