<?php

namespace Database\Seeders;

use App\Models\Fournisseur;
use Illuminate\Database\Seeder;

class FournisseurSeeder extends Seeder
{
    public function run(): void
    {
        $fournisseurs = [
            [
                'nom' => 'Tech Solutions SA',
                'email' => 'contact@techsolutions.com',
                'telephone' => '0123456789',
                'numero_fix' => '0123456700',
                'adresse' => '123 Rue de la Technologie, Paris',
            ],
            [
                'nom' => 'Global Logistics sarl',
                'email' => 'info@globallogistics.fr',
                'telephone' => '0987654321',
                'numero_fix' => '0987654300',
                'adresse' => '45 Avenue du Commerce, Lyon',
            ],
            [
                'nom' => 'Office Prime',
                'email' => 'sales@officeprime.com',
                'telephone' => '0555444333',
                'numero_fix' => '0555444000',
                'adresse' => '10 Boulevard des Affaires, Marseille',
            ],
        ];

        foreach ($fournisseurs as $f) {
            Fournisseur::updateOrCreate(['email' => $f['email']], $f);
        }
    }
}
