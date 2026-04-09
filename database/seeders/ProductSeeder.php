<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\Produit;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $informatique = Categorie::where('nom', 'Informatique')->first();
        $mobilier = Categorie::where('nom', 'Mobilier')->first();

        $products = [
            [
                'nom' => 'Ordinateur Portable Dell XPS 15',
                'description' => 'PC ultra-puissant pour les professionnels',
                'quantite' => 15,
                'seuil_min' => 3,
                'categorie_id' => $informatique->id,
                'prix' => 1899.99,
            ],
            [
                'nom' => 'Moniteur 27" LG UltraGear',
                'description' => 'Écran 4K pour graphisme et gaming',
                'quantite' => 20,
                'seuil_min' => 5,
                'categorie_id' => $informatique->id,
                'prix' => 450.00,
            ],
            [
                'nom' => 'Chaise de bureau ergonomique',
                'description' => 'Confort optimal pour de longues heures de travail',
                'quantite' => 4, // Low stock simulation
                'seuil_min' => 5,
                'categorie_id' => $mobilier->id,
                'prix' => 299.00,
            ],
            [
                'nom' => 'Clavier Mécanique RGB',
                'description' => 'Saisie rapide et tactile',
                'quantite' => 50,
                'seuil_min' => 10,
                'categorie_id' => $informatique->id,
                'prix' => 120.50,
            ],
        ];

        foreach ($products as $p) {
            Produit::updateOrCreate(['nom' => $p['nom']], $p);
        }
    }
}
