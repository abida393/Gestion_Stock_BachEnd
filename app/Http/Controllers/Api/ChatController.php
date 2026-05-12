<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alerte;
use App\Models\Prevision;
use App\Models\Produit;
use App\Models\Commande;
use App\Models\LigneCommande;
use App\Models\MouvementStock;
use App\Services\LLMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    protected $llmService;

    public function __construct(LLMService $llmService)
    {
        $this->llmService = $llmService;
    }

    /**
     * Chat général avec contexte global du stock.
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'history' => 'nullable|array',
        ]);

        $context = $this->getRelevantContext();
        
        // On ne garde que les 6 derniers messages (3 échanges)
        $history = array_slice($request->history ?? [], -6);

        // Détection de demande de graphique (Simplifiée)
        $msg = strtolower($request->message);
        if (str_contains($msg, 'graphique') || str_contains($msg, 'évolution') || str_contains($msg, 'historique')) {
            $recentMovements = MouvementStock::select(DB::raw('CAST(date_mouvement AS DATE) as date'), DB::raw('SUM(quantite) as total'))
                ->where('date_mouvement', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date')
                ->get();
            
            $context['chart_data_available'] = $recentMovements;
        }

        $response = $this->llmService->generateResponse($request->message, $context, $history);

        return response()->json([
            'response' => $response
        ]);
    }

    /**
     * Sélectionne uniquement les données pertinentes pour limiter le payload.
     */
    private function getRelevantContext()
    {
        // On envoie le top 15 des produits (par stock ou par criticité) en format ultra-slim
        $produits = Produit::orderBy('quantite', 'asc')
            ->limit(15)
            ->get(['id', 'nom', 'quantite', 'seuil_min']);


        // Les 3 dernières anomalies détectées
        $anomalies = Alerte::where('type', 'anomalie')
            ->where('est_active', true)
            ->latest('declenche_le')
            ->limit(3)
            ->get(['message', 'confiance']);

        // Les 2 dernières alertes générales
        $alertes = Alerte::where('est_active', true)
            ->where('type', '!=', 'anomalie')
            ->latest('declenche_le')
            ->limit(2)
            ->get(['message']);

        return [
            'etat_stock_partiel' => $produits,
            'dernières_anomalies' => $anomalies,
            'alertes_actives' => $alertes,
            'maintenant' => now()->format('Y-m-d H:i'),
        ];
    }



    /**
     * Explication détaillée d'une prévision spécifique.
     */
    public function explainPrevision(Request $request)
    {
        $request->validate([
            'produit_id' => 'required|exists:produits,id',
        ]);

        $produit = Produit::find($request->produit_id);
        $previsions = Prevision::where('produit_id', $request->produit_id)
            ->orderBy('periode', 'desc')
            ->limit(3)
            ->get();

        $context = [
            'produit' => [
                'nom' => $produit->nom,
                'stock_actuel' => $produit->quantite,
                'seuil_min' => $produit->seuil_min,
            ],

            'historique_previsions_ia' => $previsions,
        ];

        $prompt = "Peux-tu m'expliquer en détail l'analyse IA pour le produit {$produit->nom} ? Donne-moi une synthèse humaine, analyse les risques de rupture ou de surstock, et propose des conseils stratégiques concrets.";

        $response = $this->llmService->generateResponse($prompt, $context);

        return response()->json([
            'explanation' => $response
        ]);
    }

    /**
     * Gère les actions déclenchées depuis le chat (ex: Création de commande).
     */
    public function handleAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:CREATE_ORDER',
            'params' => 'required|array',
        ]);

        try {
            DB::beginTransaction();

            if ($request->action === 'CREATE_ORDER') {
                $params = $request->params;
                $produitId = $params['produit_id'];

                // Fallback si l'IA envoie le nom au lieu de l'ID
                if (!is_numeric($produitId)) {
                    $produit = Produit::where('nom', 'like', '%' . $produitId . '%')->first();
                    if (!$produit) {
                        return response()->json(['message' => "Produit non trouvé : {$produitId}"], 400);
                    }
                } else {
                    $produit = Produit::findOrFail($produitId);
                }

                $fournisseurId = $params['fournisseur_id'] ?? ($produit->fournisseurs->first()->id ?? null);


                if (!$fournisseurId) {
                    return response()->json(['message' => 'Aucun fournisseur associé à ce produit.'], 400);
                }
                
                $commande = Commande::create([
                    'fournisseur_id' => $fournisseurId,
                    'date_commande' => now(),
                    'statut' => 'en_attente',
                    'total' => $produit->prix * $params['quantite'],
                ]);


                LigneCommande::create([
                    'commande_id' => $commande->id,
                    'produit_id' => $produit->id,
                    'quantite' => $params['quantite'],
                    'prix' => $produit->prix,
                ]);

                DB::commit();

                return response()->json([
                    'message' => "Commande #{$commande->id} générée avec succès pour {$produit->nom} ({$params['quantite']} unités).",
                    'commande' => $commande
                ]);
            }

            return response()->json(['message' => 'Action non supportée.'], 400);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur Action Chat: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de l\'exécution de l\'action.', 'error' => $e->getMessage()], 500);
        }
    }
}
