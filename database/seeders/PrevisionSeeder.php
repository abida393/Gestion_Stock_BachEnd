<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Prevision;
use App\Models\Produit;
use Faker\Factory as Faker;

class PrevisionSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');
        $produits = Produit::all();

        if ($produits->isEmpty()) {
            return;
        }

        foreach ($produits as $produit) {
            Prevision::create([
                'produit_id' => $produit->id,
                'periode' => 'mois_prochain',
                'quantite_predite' => $faker->numberBetween(10, 200),
                'confiance' => $faker->randomFloat(2, 0.5, 0.99),
                'eoq' => $faker->numberBetween(20, 200),
                'score_anomalie' => $faker->randomFloat(2, 0, 1),
                'reasoning' => $faker->sentence,
            ]);
        }
    }
}
