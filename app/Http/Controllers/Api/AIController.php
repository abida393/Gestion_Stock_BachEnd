<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Produit;
use App\Models\MouvementStock;
use App\Models\Prevision;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AIController extends Controller
{
    /**
     * Synchronise les données avec l'agent IA Python.
     * Stocke les prédictions + raisonnement (XAI) dans la table previsions.
     */
    public function sync(Request $request)
    {
        try {
            // 1. Récupérer les données
            $produits = Produit::all();
            $mouvements = MouvementStock::all();

            $payload = [
                'produits' => $produits,
                'mouvements' => $mouvements
            ];

            // 2. Envoyer à l'agent IA (Port 5000) avec Timeout court et message poli
            try {
                $response = Http::timeout(2)->post('http://127.0.0.1:5000/predict', $payload);
                
                if ($response->failed()) {
                    Log::warning('L\'agent Python a renvoyé une erreur: ' . $response->status());
                    return response()->json(['message' => 'L\'agent d\'analyse est en cours de démarrage ou indisponible, réessayez dans un instant.'], 503);
                }

                $predictions = $response->json();
                
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($predictions)) {
                    Log::error('Erreur de parsing JSON Python (predict): ' . json_last_error_msg());
                    return response()->json(['message' => 'Format de réponse de l\'IA invalide.'], 500);
                }
            } catch (\Exception $e) {
                Log::error('Impossible de joindre l\'agent Python: ' . $e->getMessage());
                return response()->json(['message' => 'L\'agent d\'analyse est en cours de démarrage, réessayez dans un instant.'], 503);
            }


            // 3. Traiter la réponse et enregistrer dans Prevision
            $periode = now()->format('Y-m'); // e.g., 2026-05

            $count = 0;
            foreach ($predictions as $prediction) {
                if (isset($prediction['produit_id'])) {
                    Prevision::updateOrCreate(
                        [
                            'produit_id' => $prediction['produit_id'],
                            'periode'    => $periode
                        ],
                        [
                            'quantite_predite' => $prediction['quantite'] ?? 0,
                            'eoq'              => $prediction['EOQ'] ?? 0,
                            'confiance'        => $prediction['confiance'] ?? 0,
                            'score_anomalie'   => $prediction['score_anomalie'] ?? null,
                            'reasoning'        => $prediction['reasoning'] ?? null,
                        ]
                    );
                    $count++;
                }
            }

            // ── Alertes Prédictives IA ──
            $alertCount = 0;
            foreach ($predictions as $prediction) {
                $pid = $prediction['produit_id'] ?? null;
                if (!$pid) continue;

                $produit = Produit::find($pid);
                if (!$produit) continue;

                $stock = (float) $produit->quantite;
                $seuil = (float) $produit->seuil_min;
                $eoq = (float) ($prediction['EOQ'] ?? 0);
                $confiance = (float) ($prediction['confiance'] ?? 0);
                $scoreAnomalie = (float) ($prediction['score_anomalie'] ?? 0);
                $nom = $produit->nom;

                // 1. Alerte prédictive : stock proche du seuil
                if ($stock > 0 && $seuil > 0 && $stock < $seuil * 1.2) {
                    $message = $stock < $seuil
                        ? "⚠️ Rupture imminente — {$nom} : stock ({$stock} u.) sous le seuil ({$seuil} u.). L'IA recommande de commander {$eoq} unités."
                        : "📉 Stock à surveiller — {$nom} : {$stock} u. proche du seuil ({$seuil} u.).";

                    $exists = \App\Models\Alerte::where('produit_id', $pid)
                        ->where('type', 'prediction')
                        ->where('est_active', true)
                        ->exists();

                    if (!$exists) {
                        \App\Models\Alerte::create([
                            'produit_id'   => $pid,
                            'seuil'        => $seuil,
                            'type'         => 'prediction',
                            'message'      => $message,
                            'confiance'    => $confiance,
                            'est_active'   => true,
                            'declenche_le' => now(),
                        ]);
                        $alertCount++;
                    }
                }

                // 2. Alerte anomalie : score élevé
                if ($scoreAnomalie > 0.5) {
                    $message = "🔍 Anomalie détectée — {$nom} : score d'anomalie {$scoreAnomalie} (mouvement suspect identifié par l'IA).";

                    $exists = \App\Models\Alerte::where('produit_id', $pid)
                        ->where('type', 'anomalie')
                        ->where('est_active', true)
                        ->where('cree_le', '>=', now()->subHours(24))
                        ->exists();

                    if (!$exists) {
                        \App\Models\Alerte::create([
                            'produit_id'   => $pid,
                            'seuil'        => $seuil,
                            'type'         => 'anomalie',
                            'message'      => $message,
                            'confiance'    => $confiance,
                            'est_active'   => true,
                            'declenche_le' => now(),
                        ]);
                        $alertCount++;
                    }
                }
            }

            return response()->json([
                'message'               => 'Synchronisation IA réussie',
                'predictions_processed' => $count,
                'alerts_created'        => $alertCount,
                'predictions'           => $predictions,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur IA Sync: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur interne du serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Simulation What-If : envoie un scénario à l'agent IA et retourne le risque.
     */
    public function simulate(Request $request)
    {
        try {
            $validated = $request->validate([
                'produit_id'       => 'required|integer|exists:produits,id',
                'scenario_type'    => 'required|string|in:delay,demand_spike,supply_cut',
                'delay_days'       => 'nullable|integer|min:0|max:365',
                'demand_spike_pct' => 'nullable|numeric|min:0|max:500',
                'description'      => 'nullable|string|max:500',
            ]);

            // Récupérer le produit et ses mouvements
            $produit = Produit::findOrFail($validated['produit_id']);
            $mouvements = MouvementStock::where('produit_id', $produit->id)
                ->orderBy('date_mouvement', 'desc')
                ->limit(100)
                ->get();

            // Construire le payload pour l'agent Python
            $payload = [
                'produit' => [
                    'id'             => $produit->id,
                    'nom'            => $produit->nom,
                    'stock_actuel'   => $produit->quantite,
                    'seuil_minimum'  => $produit->seuil_min,
                    'prix'           => $produit->prix,
                ],
                'mouvements' => $mouvements->map(function ($m) {
                    return [
                        'type'     => $m->type,
                        'quantite' => $m->quantite,
                        'date_mouvement' => $m->date_mouvement,
                    ];
                })->toArray(),
                'scenario' => [
                    'type'             => $validated['scenario_type'],
                    'delay_days'       => $validated['delay_days'] ?? 0,
                    'demand_spike_pct' => $validated['demand_spike_pct'] ?? 0,
                    'description'      => $validated['description'] ?? '',
                ],
            ];

            // Envoyer à l'agent Python avec Timeout et Gestion d'erreur robuste
            try {
                $response = Http::timeout(2)->post('http://127.0.0.1:5000/simulate', $payload);

                if ($response->failed()) {
                    return response()->json(['message' => 'L\'agent de simulation est indisponible pour le moment.'], 503);
                }

                $result = $response->json();
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("JSON mal formé de l'agent Python.");
                }

                return response()->json($result, 200);
            } catch (\Exception $e) {
                Log::error('Erreur Simulation IA (Python): ' . $e->getMessage());
                return response()->json(['message' => 'L\'agent d\'analyse est en cours de démarrage, réessayez dans un instant.'], 503);
            }


        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Données invalides.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Erreur Simulation: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur interne du serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Score de Santé de l'Inventaire (0–100%).
     * Agrège les dernières prévisions + état actuel des stocks.
     */
    public function healthScore()
    {
        try {
            $produits = Produit::all();
            $total = $produits->count();

            if ($total === 0) {
                return response()->json([
                    'score' => 100,
                    'label' => 'Aucun produit',
                    'details' => [
                        'total'          => 0,
                        'sain_count'     => 0,
                        'rupture_count'  => 0,
                        'surstock_count' => 0,
                        'critique_count' => 0,
                    ]
                ]);
            }

            $rupture_count = 0;
            $surstock_count = 0;
            $critique_count = 0;
            $sain_count = 0;
            $total_penalty = 0;

            foreach ($produits as $produit) {
                $stock = (float) $produit->quantite;
                $seuil = (float) $produit->seuil_min;

                if ($seuil <= 0) {
                    $sain_count++;
                    continue;
                }

                $ratio = $stock / $seuil;

                if ($stock <= 0) {
                    // Rupture totale
                    $critique_count++;
                    $total_penalty += 15;
                } elseif ($ratio < 1) {
                    // Sous le seuil — risque de rupture
                    $rupture_count++;
                    $total_penalty += 10;
                } elseif ($ratio > 4) {
                    // Surstock important (argent dormant)
                    $surstock_count++;
                    $total_penalty += 5;
                } elseif ($ratio > 3) {
                    // Surstock léger
                    $surstock_count++;
                    $total_penalty += 2;
                } else {
                    $sain_count++;
                }
            }

            // Intégrer les scores d'anomalie des prévisions
            $latestPrevisions = Prevision::select('produit_id', DB::raw('MAX(id) as latest_id'))
                ->groupBy('produit_id')
                ->pluck('latest_id');

            $highAnomalies = Prevision::whereIn('id', $latestPrevisions)
                ->where('score_anomalie', '>', 0.5)
                ->count();

            $total_penalty += $highAnomalies * 3;

            // Score final
            $max_penalty = $total * 15; // pire cas : tout en rupture totale
            $score = max(0, min(100, round(100 - ($total_penalty / max($max_penalty, 1)) * 100)));

            // Label
            if ($score >= 80) {
                $label = 'Excellent';
            } elseif ($score >= 60) {
                $label = 'Bon';
            } elseif ($score >= 40) {
                $label = 'Attention';
            } elseif ($score >= 20) {
                $label = 'Critique';
            } else {
                $label = 'Urgence';
            }

            return response()->json([
                'score' => $score,
                'label' => $label,
                'details' => [
                    'total'            => $total,
                    'sain_count'       => $sain_count,
                    'rupture_count'    => $rupture_count,
                    'surstock_count'   => $surstock_count,
                    'critique_count'   => $critique_count,
                    'high_anomalies'   => $highAnomalies,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur Health Score: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur interne.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Détection d'anomalies comportementales.
     * Compare les mouvements récents aux habitudes historiques,
     * puis crée des Notifications pour les admins.
     */
    public function detectAnomalies(Request $request)
    {
        try {
            Log::info('Début Analyse Anomalies IA...');
            
            // 1. Récupérer les mouvements récents (dernières 48h)
            $since = now()->subHours(48);
            $query = MouvementStock::with('produit')
                ->where('date_mouvement', '>=', $since);

            if ($request->has('produit_id') && $request->produit_id !== 'Tous') {
                $query->where('produit_id', $request->produit_id);
            }

            $recentMovements = $query->get();

            Log::info('Mouvements récents trouvés: ' . $recentMovements->count());

            if ($recentMovements->isEmpty()) {
                return response()->json([
                    'message'         => 'Aucun mouvement récent à analyser.',
                    'anomalies'       => [],
                    'total_analyzed'  => 0,
                    'total_anomalies' => 0,
                    'notifications_created' => 0,
                ]);
            }

            // 2. Calculer le profil historique de chaque produit concerné
            $produitIds = $recentMovements->pluck('produit_id')->unique();
            $profils = [];

            foreach ($produitIds as $pid) {
                $historique = MouvementStock::where('produit_id', $pid)
                    ->where('date_mouvement', '<', $since)
                    ->get();

                $quantities = $historique->pluck('quantite')->map(fn($q) => (float) $q);
                $hours = $historique->map(function ($m) {
                    try {
                        return $m->date_mouvement ? $m->date_mouvement->hour : 12;
                    } catch (\Exception $e) {
                        return 12;
                    }
                });

                $avg_qty = $quantities->count() > 0 ? $quantities->avg() : 0;
                $std_qty = $quantities->count() > 1 
                    ? sqrt($quantities->map(fn($q) => pow($q - $avg_qty, 2))->avg())
                    : $avg_qty * 0.3;

                $usual_hours = $hours->countBy()->keys()->sort()->values()->toArray();
                if (empty($usual_hours)) {
                    $usual_hours = range(8, 17);
                }

                $days_span = max(1, $historique->count() > 0
                    ? now()->diffInDays($historique->min('date_mouvement'))
                    : 30);
                $avg_daily_count = $historique->count() / $days_span;

                $profils[(string) $pid] = [
                    'avg_qty'         => round($avg_qty, 2),
                    'std_qty'         => round($std_qty, 2),
                    'usual_hours'     => $usual_hours,
                    'avg_daily_count' => round($avg_daily_count, 2),
                ];
            }

            // 3. Préparer les mouvements pour l'agent Python
            $mouvementsPayload = $recentMovements->map(function ($m) {
                return [
                    'id'              => $m->id,
                    'produit_id'      => $m->produit_id,
                    'produit_nom'     => $m->produit->nom ?? 'Inconnu',
                    'quantite'        => $m->quantite,
                    'type'            => $m->type,
                    'date_mouvement'  => $m->date_mouvement ? $m->date_mouvement->toISOString() : null,
                ];
            })->toArray();

            // 4. Envoyer à l'agent Python avec Timeout Robuste
            try {
                $response = Http::timeout(2)->post('http://127.0.0.1:5000/detect-anomalies', [
                    'mouvements_recents' => $mouvementsPayload,
                    'profils'            => $profils,
                ]);

                if ($response->failed()) {
                    Log::error('Erreur agent Python: ' . $response->status());
                    return response()->json(['message' => 'L\'agent d\'audit est indisponible pour le moment.'], 503);
                }

                $result = $response->json();
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON invalide de l\'agent Python (anomalies)');
                    return response()->json(['message' => 'Erreur technique lors de l\'audit.'], 500);
                }
            } catch (\Exception $e) {
                Log::error('Timeout ou Erreur de connexion Python (Anomalies): ' . $e->getMessage());
                return response()->json(['message' => 'L\'agent d\'analyse est en cours de démarrage, réessayez dans un instant.'], 503);
            }

            $anomalies = $result['anomalies'] ?? [];
            Log::info('Anomalies détectées par l\'IA: ' . count($anomalies));

            // 5. Créer des Notifications pour les admins
            $notifCount = 0;
            if (!empty($anomalies)) {
                $adminUsers = User::all(); // On prend tout le monde si le role fail
                try {
                    $adminsWithRole = User::role('admin')->get();
                    if ($adminsWithRole->isNotEmpty()) {
                        $adminUsers = $adminsWithRole;
                    }
                } catch (\Exception $e) {
                    Log::warning('Role admin introuvable, notification à tous les utilisateurs.');
                }

                foreach ($anomalies as $anomaly) {
                    $severity = $anomaly['severity'] ?? 0;
                    if ($severity < 30) continue;

                    $typeLabel = $severity >= 70 ? 'critique' : ($severity >= 50 ? 'important' : 'info');
                    $message = sprintf(
                        "🔍 Anomalie comportementale détectée — %s : %s (score: %d/100)",
                        $anomaly['produit_nom'] ?? 'Produit inconnu',
                        $anomaly['description'] ?? 'Comportement inhabituel',
                        $severity
                    );

                    foreach ($adminUsers as $admin) {
                        try {
                            $exists = Notification::where('utilisateur_id', $admin->id)
                                ->where('message', $message)
                                ->where('cree_le', '>=', now()->subHours(24))
                                ->exists();

                            if (!$exists) {
                                Notification::create([
                                    'utilisateur_id' => $admin->id,
                                    'message'        => $message,
                                    'type'           => 'anomalie_' . $typeLabel,
                                    'lu'             => false,
                                ]);
                                $notifCount++;
                            }
                        } catch (\Exception $e) {
                            Log::error('Erreur création notification: ' . $e->getMessage());
                        }
                    }
                }
            }

            return response()->json([
                'message'                => 'Analyse comportementale terminée.',
                'anomalies'              => $anomalies,
                'total_analyzed'         => $result['total_analyzed'] ?? 0,
                'total_anomalies'        => $result['total_anomalies'] ?? 0,
                'notifications_created'  => $notifCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur critique Audit IA: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['message' => 'Erreur lors de l\'audit.', 'error' => $e->getMessage()], 500);
        }
    }
}
