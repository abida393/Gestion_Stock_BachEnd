<?php

namespace Database\Seeders;

use App\Models\Fournisseur;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class FournisseurSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');

        for ($i = 0; $i < 30; $i++) {
            Fournisseur::create([
                'nom' => $faker->company,
                'email' => $faker->unique()->companyEmail,
                'telephone' => $faker->phoneNumber,
                'numero_fix' => $faker->phoneNumber,
                'adresse' => $faker->address,
            ]);
        }
    }
}
