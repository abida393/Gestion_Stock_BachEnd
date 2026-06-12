<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Produit;
use App\Models\Fournisseur;
use App\Models\Commande;
use App\Models\LigneCommande;
use App\Models\MouvementStock;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * SaaSFeaturesSeeder
 *
 * Peuple les données de test pour les 4 nouvelles fonctionnalités :
 *   1. Stock en Transit
 *   2. Rating Fournisseur A/B/C (via commandes avec dates livraison)
 *   3. Alertes Prédictives (mouvements de sortie en hausse)
 *   4. Export CSV (données variées pour un export significatif)
 */
class SaaSFeaturesSeeder extends Seeder
{
    public function run(): void
    {
        $admin  = User::first();
        $adminId = $admin?->id ?? 1;

        echo "\n🚀 Démarrage du SaaSFeaturesSeeder...\n\n";

        // ════════════════════════════════════════════════════════
        // 1. STOCK EN TRANSIT
        // ════════════════════════════════════════════════════════
        echo "📦 [1/4] Stock en Transit...\n";

        // Prend 5 produits existants et leur affecte un stock en transit
        $transitData = [
            ['stock_en_transit' => 50],
            ['stock_en_transit' => 20],
            ['stock_en_transit' => 100],
            ['stock_en_transit' => 15],
            ['stock_en_transit' => 75],
        ];

        $products = Produit::take(5)->get();
        foreach ($products as $idx => $p) {
            $transit = $transitData[$idx]['stock_en_transit'];
            $p->update(['stock_en_transit' => $transit]);
            echo "   → {$p->nom} : +{$transit} en transit\n";
        }

        // ════════════════════════════════════════════════════════
        // 2. RATING FOURNISSEUR A/B/C
        // Crée des commandes livrées avec des dates de livraison variées
        // ════════════════════════════════════════════════════════
        echo "\n⭐ [2/4] Rating Fournisseur A/B/C...\n";

        $fournisseurs = Fournisseur::take(5)->get();

        if ($fournisseurs->isEmpty()) {
            echo "   ⚠ Aucun fournisseur trouvé. Ignoré.\n";
        } else {
            $ratingScenarios = [
                // [retard_jours] → Rating attendu
                ['fournisseur_idx' => 0, 'orders' => [0, -1, 0, 1, 0]],  // → A (livré à temps ou en avance)
                ['fournisseur_idx' => 1, 'orders' => [3, 4, 2, 5, 3]],   // → B (2-5j de retard)
                ['fournisseur_idx' => 2, 'orders' => [8, 10, 7, 12, 9]], // → C (>5j de retard)
                ['fournisseur_idx' => 3, 'orders' => [0, 0, 1, 0, 2]],   // → A
                ['fournisseur_idx' => 4, 'orders' => [3, 6, 2, 4, 5]],   // → B
            ];

            $produit = Produit::first();

            foreach ($ratingScenarios as $scenario) {
                $fournisseur = $fournisseurs->get($scenario['fournisseur_idx']);
                if (!$fournisseur) continue;

                $ratingLabel = match(true) {
                    max($scenario['orders']) <= 1   => 'A',
                    max($scenario['orders']) <= 5   => 'B',
                    default                          => 'C',
                };

                echo "   → Fournisseur \"{$fournisseur->nom}\" : Rating attendu {$ratingLabel}\n";

                foreach ($scenario['orders'] as $offset => $retardJours) {
                    $dateCommande   = now()->subDays(60 + $offset * 7);
                    $datePrevue     = $dateCommande->copy()->addDays(10);
                    $dateReception  = $datePrevue->copy()->addDays($retardJours);

                    DB::transaction(function () use ($fournisseur, $produit, $dateCommande, $datePrevue, $dateReception, $adminId) {
                        $commande = Commande::create([
                            'fournisseur_id'        => $fournisseur->id,
                            'date_commande'         => $dateCommande,
                            'statut'                => 'livree',
                            'total'                 => ($produit->prix ?? 100) * 10,
                            'date_prevue_livraison' => $datePrevue,
                            'date_reception_reelle' => $dateReception,
                        ]);

                        LigneCommande::create([
                            'commande_id' => $commande->id,
                            'produit_id'  => $produit->id,
                            'quantite'    => 10,
                            'prix'        => $produit->prix ?? 100,
                        ]);
                    });
                }
            }
        }

        // ════════════════════════════════════════════════════════
        // 3. ALERTES PRÉDICTIVES — Hausse de demande >20%
        // Crée des sorties qui doublent entre J-60/J-30 et J-30/aujourd'hui
        // ════════════════════════════════════════════════════════
        echo "\n⚡ [3/4] Alertes Prédictives (hausse demande)...\n";

        // Sélectionne 3 produits pour simuler des hausses
        $demandProducts = Produit::skip(5)->take(3)->get();

        foreach ($demandProducts as $idx => $p) {
            // Période précédente (J-60 → J-30) : faible demande
            $previousSorties = [8, 6, 9, 7, 10]; // ~40 unités/mois

            // Période récente (J-30 → aujourd'hui) : forte demande (+60% à +150%)
            $recentSorties = match($idx) {
                0 => [15, 18, 12, 20, 14], // +75% → Vigilance Haute
                1 => [25, 22, 28, 30, 20], // +200% → Critique
                2 => [12, 14, 11, 16, 13], // +55% → Vigilance Haute
                default => [10, 10, 10, 10, 10],
            };

            echo "   → {$p->nom} : Simulation hausse demande\n";

            // Période précédente
            foreach ($previousSorties as $dayIdx => $qty) {
                $daysAgo = 60 - ($dayIdx * 5);
                MouvementStock::create([
                    'produit_id'     => $p->id,
                    'utilisateur_id' => $adminId,
                    'type'           => 'sortie',
                    'quantite'       => $qty,
                    'date_mouvement' => now()->subDays($daysAgo)->setHour(rand(8, 17)),
                    'note'           => 'Consommation historique (seed)',
                    'stock_apres'    => max(0, $p->quantite - $qty),
                ]);
            }

            // Période récente
            foreach ($recentSorties as $dayIdx => $qty) {
                $daysAgo = 25 - ($dayIdx * 4);
                MouvementStock::create([
                    'produit_id'     => $p->id,
                    'utilisateur_id' => $adminId,
                    'type'           => 'sortie',
                    'quantite'       => $qty,
                    'date_mouvement' => now()->subDays(max(0, $daysAgo))->setHour(rand(8, 17)),
                    'note'           => 'Consommation récente (seed)',
                    'stock_apres'    => max(0, $p->quantite - $qty),
                ]);
            }
        }

        // ════════════════════════════════════════════════════════
        // 4. PRODUITS AVEC stock_reserve POUR EXPORT CSV COMPLET
        // ════════════════════════════════════════════════════════
        echo "\n📊 [4/4] Stock réservé pour données CSV variées...\n";

        $reserveData = Produit::take(8)->get();
        foreach ($reserveData as $idx => $p) {
            $reserve = [5, 0, 10, 0, 3, 0, 8, 2][$idx] ?? 0;
            if ($reserve > 0 && $p->quantite >= $reserve) {
                $p->update(['stock_reserve' => $reserve]);
                echo "   → {$p->nom} : {$reserve} réservés\n";
            }
        }

        // ════════════════════════════════════════════════════════
        // RÉSUMÉ
        // ════════════════════════════════════════════════════════
        echo "\n✅ SaaSFeaturesSeeder terminé !\n";
        echo "   📦 5 produits avec stock_en_transit\n";
        echo "   ⭐ 5 fournisseurs avec historique de livraison (ratings A/B/C)\n";
        echo "   ⚡ 3 produits avec hausse de demande simulée (+55% à +200%)\n";
        echo "   📊 Données CSV variées (réservations, transit, statuts)\n\n";
        echo "   → Ouvrez le Dashboard pour voir les nouveaux KPIs et badges !\n";
        echo "   → Allez dans Produits > Export CSV pour tester l'export.\n\n";
    }
}
