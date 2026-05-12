<?php

namespace App\Services;

use App\Models\Produit;
use App\Models\MouvementStock;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    /**
     * Analyse un mouvement de sortie avant validation.
     * Retourne ['allowed' => true] ou ['allowed' => false, 'reason' => '...']
     */
    public function validateMovement(Produit $produit, string $type, float $quantite)
    {
        if ($type !== 'sortie') {
            return ['allowed' => true];
        }

        try {
            // Récupérer historique récent pour contexte
            $historique = MouvementStock::where('produit_id', $produit->id)
                ->orderBy('date_mouvement', 'desc')
                ->limit(20)
                ->get();

            $payload = [
                'produit' => [
                    'id' => $produit->id,
                    'nom' => $produit->nom,
                    'stock_actuel' => $produit->quantite,
                    'seuil_min' => $produit->seuil_min,
                ],
                'tentative' => [
                    'type' => $type,
                    'quantite' => $quantite,
                    'heure' => now()->hour,
                ],
                'historique' => $historique
            ];

            // Appeler l'agent Python sur un nouvel endpoint 'validate-transaction'
            $response = Http::timeout(5)->post('http://127.0.0.1:5000/validate-transaction', $payload);

            if ($response->failed()) {
                // En cas d'erreur IA, on autorise par défaut pour ne pas bloquer le business
                Log::warning('IA Validation indisponible. Passage en mode manuel.');
                return ['allowed' => true];
            }

            $result = $response->json();
            
            return [
                'allowed' => $result['decision'] === 'APPROUVE',
                'reason'  => $result['reason'] ?? 'Action bloquée par l\'Analyse de Risque IA.',
                'score'   => $result['risk_score'] ?? 0
            ];

        } catch (\Exception $e) {
            Log::error('Erreur AIService Validation: ' . $e->getMessage());
            return ['allowed' => true];
        }
    }
}
