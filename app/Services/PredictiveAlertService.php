<?php

namespace App\Services;

use App\Models\MouvementStock;
use App\Models\Produit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PredictiveAlertService
{
    /**
     * Analyze demand trends using a moving average comparison.
     * If demand increased >= 20% over the past 30 days vs previous 30 days,
     * the product is flagged as "Vigilance Haute".
     */
    public function analyzeAll(): Collection
    {
        $now        = now();
        $period1End = $now->copy();
        $period1Start = $now->copy()->subDays(30);
        $period2End   = $period1Start->copy();
        $period2Start = $now->copy()->subDays(60);

        // Aggregate sorties per product for the two windows
        $buildWindow = function ($start, $end) {
            return MouvementStock::where('type', 'sortie')
                ->whereBetween('date_mouvement', [$start, $end])
                ->select('produit_id', DB::raw('SUM(quantite) as total_qty'))
                ->groupBy('produit_id')
                ->pluck('total_qty', 'produit_id');
        };

        $recent   = $buildWindow($period1Start, $period1End); // last 30d
        $previous = $buildWindow($period2Start, $period2End); // 30d before that

        $alerts = collect();

        // Only scan products that had activity recently
        foreach ($recent as $produitId => $recentQty) {
            $prevQty = $previous->get($produitId, 0);

            // Compute percentage change using moving average
            // If previous is 0, use a small baseline to avoid division by zero
            $baseline = max($prevQty, 1);
            $changePct = (($recentQty - $prevQty) / $baseline) * 100;

            if ($changePct >= 20) {
                $produit = Produit::find($produitId);
                if (!$produit) continue;

                // Suggested threshold = current seuil_min * (1 + growth_rate)
                $growthFactor   = 1 + ($changePct / 100);
                $seuilSuggere   = (int) round($produit->seuil_min * $growthFactor);

                $alerts->push([
                    'produit_id'    => $produitId,
                    'produit_nom'   => $produit->nom,
                    'stock_actuel'  => $produit->stock_disponible,
                    'seuil_actuel'  => $produit->seuil_min,
                    'seuil_suggere' => $seuilSuggere,
                    'hausse_pct'    => round($changePct, 1),
                    'recent_qty'    => $recentQty,
                    'previous_qty'  => $prevQty,
                    'niveau'        => $changePct >= 50 ? 'critique' : 'vigilance',
                ]);
            }
        }

        return $alerts->sortByDesc('hausse_pct')->values();
    }
}
