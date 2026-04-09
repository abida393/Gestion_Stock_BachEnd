<?php

namespace Database\Seeders;

use App\Models\Categorie;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['nom' => 'Électronique', 'description' => 'Composants et appareils électroniques'],
            ['nom' => 'Mobilier', 'description' => 'Bureaux, chaises et rangements'],
            ['nom' => 'Outillage', 'description' => 'Outils de maintenance et construction'],
            ['nom' => 'Fournitures de bureau', 'description' => 'Papeterie et consommables'],
            ['nom' => 'Informatique', 'description' => 'Ordinateurs, périphériques et réseaux'],
        ];

        foreach ($categories as $category) {
            Categorie::updateOrCreate(['nom' => $category['nom']], $category);
        }
    }
}
