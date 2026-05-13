<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Produit;
use App\Models\MouvementStock;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class AIAnalysisController extends Controller
{
    /**
     * Perform ABC Analysis based on Stock Value and Rotation
     */
    public function abcAnalysis()
    {
        $produits = Produit::with(['mouvementStock' => function($q) {
            $q->where('cree_le', '>=', now()->subDays(30));
        }])->get();

        if ($produits->isEmpty()) {
            return response()->json(['message' => 'Aucune donnée pour l\'analyse.'], 404);
        }

        // 1. Calculate Value and Frequency
        $data = $produits->map(function ($p) {
            $value = ($p->quantite ?? 0) * ($p->prix ?? 0);
            $frequency = $p->mouvementStock->count();
            return [
                'id' => $p->id,
                'nom' => $p->nom,
                'sku' => $p->sku,
                'stock' => $p->quantite,
                'prix' => $p->prix,
                'valeur_totale' => $value,
                'frequence_30j' => $frequency,
            ];
        });

        // 2. ABC by Value
        $sortedByValue = $data->sortByDesc('valeur_totale')->values();
        $totalValue = $sortedByValue->sum('valeur_totale');
        $runningValue = 0;
        
        $valueAnalysis = $sortedByValue->map(function ($item) use ($totalValue, &$runningValue) {
            $runningValue += $item['valeur_totale'];
            $pct = $totalValue > 0 ? ($runningValue / $totalValue) * 100 : 0;
            
            if ($pct <= 70) $class = 'A';
            elseif ($pct <= 90) $class = 'B';
            else $class = 'C';
            
            $item['classe_valeur'] = $class;
            return $item;
        });

        // 3. ABC by Rotation (Frequency)
        $sortedByFreq = $data->sortByDesc('frequence_30j')->values();
        $totalFreq = $sortedByFreq->sum('frequence_30j');
        $runningFreq = 0;

        $rotationAnalysis = $sortedByFreq->map(function ($item) use ($totalFreq, &$runningFreq) {
            $runningFreq += $item['frequence_30j'];
            $pct = $totalFreq > 0 ? ($runningFreq / $totalFreq) * 100 : 0;

            if ($pct <= 70) $class = 'A';
            elseif ($pct <= 90) $class = 'B';
            else $class = 'C';

            return [
                'id' => $item['id'],
                'classe_rotation' => $class
            ];
        })->pluck('classe_rotation', 'id');

        // Combine
        $final = $valueAnalysis->map(function($item) use ($rotationAnalysis) {
            $item['classe_rotation'] = $rotationAnalysis[$item['id']] ?? 'C';
            
            // Suggestion logic
            $suggestions = [];
            if ($item['classe_rotation'] === 'C' && $item['classe_valeur'] === 'A') {
                $suggestions[] = "Stock dormant de haute valeur. Envisager une promotion ou un retour fournisseur.";
            }
            if ($item['classe_rotation'] === 'A' && $item['stock'] < 10) {
                $suggestions[] = "Produit à forte rotation en stock bas. Réapprovisionnement prioritaire.";
            }
            
            $item['suggestions'] = $suggestions;
            return $item;
        });

        return response()->json([
            'timestamp' => now(),
            'summary' => [
                'total_items' => $final->count(),
                'class_a_count' => $final->where('classe_valeur', 'A')->count(),
                'class_b_count' => $final->where('classe_valeur', 'B')->count(),
                'class_c_count' => $final->where('classe_valeur', 'C')->count(),
            ],
            'items' => $final
        ]);
    }

    /**
     * Get Audit Logs enriched with names
     */
    public function auditLogs()
    {
        $logs = AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        // Collect IDs to fetch names in bulk
        $productIds = [];
        $supplierIds = [];
        
        foreach ($logs as $log) {
            foreach (['old_values', 'new_values'] as $field) {
                if ($log->$field && is_array($log->$field)) {
                    if (isset($log->$field['produit_id'])) $productIds[] = $log->$field['produit_id'];
                    if (isset($log->$field['fournisseur_id'])) $supplierIds[] = $log->$field['fournisseur_id'];
                }
            }
        }
        
        $products = \App\Models\Produit::whereIn('id', array_unique($productIds))->pluck('nom', 'id');
        $suppliers = \App\Models\Fournisseur::whereIn('id', array_unique($supplierIds))->pluck('nom', 'id');

        $logs->getCollection()->transform(function ($log) use ($products, $suppliers) {
            foreach (['old_values', 'new_values'] as $field) {
                if ($log->$field && is_array($log->$field)) {
                    $vals = $log->$field;
                    if (isset($vals['produit_id'])) {
                        $vals['product_name'] = $products[$vals['produit_id']] ?? "Inconnu (#{$vals['produit_id']})";
                    }
                    if (isset($vals['fournisseur_id'])) {
                        $vals['supplier_name'] = $suppliers[$vals['fournisseur_id']] ?? "Inconnu (#{$vals['fournisseur_id']})";
                    }
                    $log->$field = $vals;
                }
            }
            return $log;
        });

        return response()->json($logs);
    }
}
