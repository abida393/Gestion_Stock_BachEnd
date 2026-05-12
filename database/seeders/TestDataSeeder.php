<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categorie;
use App\Models\Produit;
use App\Models\MouvementStock;
use App\Models\User;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Get first admin user
        $admin = User::first();
        $adminId = $admin ? $admin->id : 1;

        // ── Catégories ──
        $cats = [
            ['nom' => 'Électronique', 'description' => 'Appareils et composants électroniques'],
            ['nom' => 'Fournitures Bureau', 'description' => 'Papeterie et consommables bureau'],
            ['nom' => 'Alimentation', 'description' => 'Produits alimentaires et boissons'],
            ['nom' => 'Nettoyage', 'description' => 'Produits d\'entretien et hygiène'],
            ['nom' => 'Outillage', 'description' => 'Outils et équipements techniques'],
        ];

        $catIds = [];
        foreach ($cats as $cat) {
            $c = Categorie::firstOrCreate(['nom' => $cat['nom']], $cat);
            $catIds[$cat['nom']] = $c->id;
        }

        // ── Produits (Note: No SKU column in table) ──
        $produits = [
            ['nom' => 'Clavier Logitech MX Keys', 'categorie_id' => $catIds['Électronique'], 'quantite' => 45, 'seuil_min' => 10, 'prix' => 899.00, 'description' => 'Clavier haut de gamme'],
            ['nom' => 'Souris sans fil HP',        'categorie_id' => $catIds['Électronique'], 'quantite' => 8,  'seuil_min' => 15, 'prix' => 249.00, 'description' => 'Souris ergonomique'],
            ['nom' => 'Écran Dell 27"',             'categorie_id' => $catIds['Électronique'], 'quantite' => 12, 'seuil_min' => 5,  'prix' => 3200.00, 'description' => 'Écran 4K IPS'],
            ['nom' => 'Câble HDMI 2m',              'categorie_id' => $catIds['Électronique'], 'quantite' => 3,  'seuil_min' => 20, 'prix' => 45.00, 'description' => 'Câble haute vitesse'],
            ['nom' => 'Ramette Papier A4',          'categorie_id' => $catIds['Fournitures Bureau'], 'quantite' => 200, 'seuil_min' => 50, 'prix' => 55.00, 'description' => '80g/m2'],
            ['nom' => 'Stylo BIC bleu (x50)',       'categorie_id' => $catIds['Fournitures Bureau'], 'quantite' => 30,  'seuil_min' => 10, 'prix' => 120.00, 'description' => 'Stylos à bille'],
            ['nom' => 'Cartouche Encre HP 305',     'categorie_id' => $catIds['Fournitures Bureau'], 'quantite' => 5,   'seuil_min' => 8,  'prix' => 189.00, 'description' => 'Noir'],
            ['nom' => 'Eau Minérale 1.5L (x6)',     'categorie_id' => $catIds['Alimentation'], 'quantite' => 80, 'seuil_min' => 30, 'prix' => 18.00, 'description' => 'Pack de 6'],
            ['nom' => 'Café Moulu 500g',            'categorie_id' => $catIds['Alimentation'], 'quantite' => 15, 'seuil_min' => 5,  'prix' => 65.00, 'description' => 'Arabica'],
            ['nom' => 'Sucre 1kg',                  'categorie_id' => $catIds['Alimentation'], 'quantite' => 0,  'seuil_min' => 10, 'prix' => 12.00, 'description' => 'Sucre blanc'],
            ['nom' => 'Javel 2L',                   'categorie_id' => $catIds['Nettoyage'], 'quantite' => 25, 'seuil_min' => 10, 'prix' => 22.00, 'description' => 'Désinfectant'],
            ['nom' => 'Savon Liquide 5L',           'categorie_id' => $catIds['Nettoyage'], 'quantite' => 4,  'seuil_min' => 6,  'prix' => 85.00, 'description' => 'Antibactérien'],
            ['nom' => 'Perceuse Bosch Pro',         'categorie_id' => $catIds['Outillage'], 'quantite' => 7,  'seuil_min' => 3,  'prix' => 1450.00, 'description' => 'Sans fil'],
            ['nom' => 'Tournevis Set (x12)',        'categorie_id' => $catIds['Outillage'], 'quantite' => 18, 'seuil_min' => 5,  'prix' => 320.00, 'description' => 'Précision'],
        ];

        $produitIds = [];
        foreach ($produits as $p) {
            $prod = Produit::firstOrCreate(['nom' => $p['nom']], $p);
            $produitIds[] = $prod->id;
        }

        // ── Mouvements de stock (entrées et sorties sur les derniers 30 jours) ──
        $mouvements = [
            // Entrées
            ['produit_idx' => 0,  'type' => 'entree', 'quantite' => 50, 'days_ago' => 28],
            ['produit_idx' => 1,  'type' => 'entree', 'quantite' => 30, 'days_ago' => 25],
            ['produit_idx' => 2,  'type' => 'entree', 'quantite' => 20, 'days_ago' => 20],
            ['produit_idx' => 3,  'type' => 'entree', 'quantite' => 40, 'days_ago' => 18],
            ['produit_idx' => 4,  'type' => 'entree', 'quantite' => 300,'days_ago' => 15],
            ['produit_idx' => 5,  'type' => 'entree', 'quantite' => 50, 'days_ago' => 14],
            ['produit_idx' => 6,  'type' => 'entree', 'quantite' => 15, 'days_ago' => 12],
            ['produit_idx' => 7,  'type' => 'entree', 'quantite' => 100,'days_ago' => 10],
            ['produit_idx' => 8,  'type' => 'entree', 'quantite' => 20, 'days_ago' => 10],
            ['produit_idx' => 9,  'type' => 'entree', 'quantite' => 30, 'days_ago' => 8],
            ['produit_idx' => 10, 'type' => 'entree', 'quantite' => 30, 'days_ago' => 7],
            ['produit_idx' => 11, 'type' => 'entree', 'quantite' => 10, 'days_ago' => 6],
            ['produit_idx' => 12, 'type' => 'entree', 'quantite' => 10, 'days_ago' => 5],
            ['produit_idx' => 13, 'type' => 'entree', 'quantite' => 25, 'days_ago' => 4],

            // Sorties
            ['produit_idx' => 0,  'type' => 'sortie', 'quantite' => 5,  'days_ago' => 22],
            ['produit_idx' => 1,  'type' => 'sortie', 'quantite' => 22, 'days_ago' => 20],
            ['produit_idx' => 2,  'type' => 'sortie', 'quantite' => 8,  'days_ago' => 15],
            ['produit_idx' => 3,  'type' => 'sortie', 'quantite' => 37, 'days_ago' => 12],
            ['produit_idx' => 4,  'type' => 'sortie', 'quantite' => 100,'days_ago' => 10],
            ['produit_idx' => 5,  'type' => 'sortie', 'quantite' => 20, 'days_ago' => 8],
            ['produit_idx' => 6,  'type' => 'sortie', 'quantite' => 10, 'days_ago' => 6],
            ['produit_idx' => 7,  'type' => 'sortie', 'quantite' => 20, 'days_ago' => 5],
            ['produit_idx' => 8,  'type' => 'sortie', 'quantite' => 5,  'days_ago' => 4],
            ['produit_idx' => 9,  'type' => 'sortie', 'quantite' => 30, 'days_ago' => 3],
            ['produit_idx' => 10, 'type' => 'sortie', 'quantite' => 5,  'days_ago' => 2],
            ['produit_idx' => 11, 'type' => 'sortie', 'quantite' => 6,  'days_ago' => 2],
            ['produit_idx' => 12, 'type' => 'sortie', 'quantite' => 3,  'days_ago' => 1],
            ['produit_idx' => 13, 'type' => 'sortie', 'quantite' => 7,  'days_ago' => 1],

            // Extra sorties récentes (pour anomalies)
            ['produit_idx' => 3,  'type' => 'sortie', 'quantite' => 15, 'days_ago' => 1, 'hour' => 3],  // Sortie nocturne suspecte
            ['produit_idx' => 1,  'type' => 'sortie', 'quantite' => 5,  'days_ago' => 0],
            ['produit_idx' => 6,  'type' => 'sortie', 'quantite' => 3,  'days_ago' => 0],
            ['produit_idx' => 9,  'type' => 'sortie', 'quantite' => 10, 'days_ago' => 0, 'hour' => 23], // Sortie nocturne suspecte
        ];

        foreach ($mouvements as $m) {
            $pid = $produitIds[$m['produit_idx']] ?? null;
            if (!$pid) continue;

            $date = now()->subDays($m['days_ago']);
            if (isset($m['hour'])) {
                $date = $date->setHour($m['hour'])->setMinute(rand(0,59));
            } else {
                $date = $date->setHour(rand(8, 17))->setMinute(rand(0,59));
            }

            // Note column instead of motif
            MouvementStock::create([
                'produit_id'      => $pid,
                'utilisateur_id'  => $adminId,
                'type'            => $m['type'],
                'quantite'        => $m['quantite'],
                'date_mouvement'  => $date,
                'note'            => $m['type'] === 'entree' ? 'Réapprovisionnement' : 'Consommation',
                'stock_apres'     => 0, // Should ideally be calculated, but using 0 for simplicity if observers don't handle it
            ]);
        }

        echo "✅ Données de test insérées :\n";
        echo "   - " . count($cats) . " catégories\n";
        echo "   - " . count($produits) . " produits\n";
        echo "   - " . count($mouvements) . " mouvements\n";
    }
}
