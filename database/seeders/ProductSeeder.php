<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\Produit;
use App\Models\Fournisseur;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');
        $categories = Categorie::all();
        $fournisseurs = Fournisseur::all();

        if ($categories->isEmpty()) {
            return;
        }

        $products = [];
        for ($i = 0; $i < 100; $i++) {
            $produit = Produit::create([
                'nom' => $faker->unique()->words(3, true) . ' ' . $faker->lexify('???'),
                'sku' => $faker->unique()->ean13,
                'description' => $faker->paragraph,
                'quantite' => $faker->numberBetween(0, 500),
                'seuil_min' => $faker->numberBetween(5, 50),
                'categorie_id' => $categories->random()->id,
                'prix' => $faker->randomFloat(2, 5, 2000),
            ]);

            if ($fournisseurs->isNotEmpty()) {
                $produit->fournisseurs()->attach(
                    $fournisseurs->random(rand(1, 3))->pluck('id')->toArray(),
                    ['prix_unitaire' => $faker->randomFloat(2, 4, 1900), 'delai_livraison_jours' => $faker->numberBetween(1, 30)]
                );
            }
        }
    }
}
