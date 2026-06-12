<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rapport;
use App\Models\User;
use Faker\Factory as Faker;

class RapportSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');
        $admin = User::first();

        if (!$admin) {
            return;
        }

        for ($i = 0; $i < 20; $i++) {
            Rapport::create([
                'utilisateur_id' => $admin->id,
                'type' => $faker->randomElement(['mensuel', 'hebdomadaire', 'alerte']),
                'chemin_fichier' => '/reports/' . $faker->word . '_' . $faker->unixTime . '.pdf',
                'genere_le' => $faker->dateTimeBetween('-3 months', 'now'),
            ]);
        }
    }
}
