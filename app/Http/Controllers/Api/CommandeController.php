<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommandeRequest;
use App\Http\Resources\CommandeResource;
use App\Models\Commande;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommandeController extends Controller
{
    /**
     * List all orders (filter: statut, fournisseur, date)
     */
    public function index(Request $request)
    {
        $query = Commande::query()->orderBy('id', 'desc');
        
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }
        
        if ($request->has('fournisseur_id')) {
            $query->where('fournisseur_id', $request->fournisseur_id);
        }
        
        return CommandeResource::collection(
            $query->with(['fournisseur', 'lignesCommande.produit'])->paginate(15)
        );
    }

    /**
     * Create order with lines — increments stock_en_transit automatically
     */
    public function store(CommandeRequest $request)
    {
        return DB::transaction(function () use ($request) {
            // Calculate expected delivery date using supplier lead time if available
            $datePrevue = $request->date_prevue_livraison ?? null;

            $commande = Commande::create([
                'fournisseur_id'       => $request->fournisseur_id,
                'date_commande'        => $request->date_commande,
                'statut'               => 'en_attente',
                'total'                => collect($request->lignes)->sum(fn($l) => $l['quantite'] * $l['prix']),
                'date_prevue_livraison' => $datePrevue,
            ]);

            foreach ($request->lignes as $ligne) {
                $commande->lignesCommande()->create($ligne);

                // ── Incrémenter stock_en_transit ──
                $produit = Produit::find($ligne['produit_id']);
                if ($produit) {
                    $produit->increment('stock_en_transit', $ligne['quantite']);
                }
            }

            return new CommandeResource($commande->load('lignesCommande'));
        });
    }

    /**
     * Get order with line items
     */
    public function show(Commande $commande)
    {
        return new CommandeResource($commande->load(['fournisseur', 'lignesCommande.produit']));
    }

    /**
     * Update order (limited to en_attente status)
     */
    public function update(CommandeRequest $request, Commande $commande)
    {
        if ($commande->statut !== 'en_attente') {
            return response()->json(['message' => 'Impossible de modifier une commande déjà livrée ou annulée.'], 422);
        }

        return DB::transaction(function () use ($request, $commande) {
            // Recalculate transit delta
            foreach ($commande->lignesCommande as $oldLigne) {
                $produit = $oldLigne->produit;
                if ($produit) {
                    $produit->decrement('stock_en_transit', $oldLigne->quantite);
                }
            }

            $commande->update([
                'fournisseur_id'       => $request->fournisseur_id,
                'date_commande'        => $request->date_commande,
                'date_prevue_livraison' => $request->date_prevue_livraison,
                'total'                => collect($request->lignes)->sum(fn($l) => $l['quantite'] * $l['prix']),
            ]);

            $commande->lignesCommande()->delete();
            foreach ($request->lignes as $ligne) {
                $commande->lignesCommande()->create($ligne);
                $produit = Produit::find($ligne['produit_id']);
                if ($produit) {
                    $produit->increment('stock_en_transit', $ligne['quantite']);
                }
            }

            return new CommandeResource($commande->load('lignesCommande'));
        });
    }

    /**
     * Cancel / delete order — reverses transit stock
     */
    public function destroy(Commande $commande)
    {
        if ($commande->statut === 'livree') {
            return response()->json(['message' => 'Impossible de supprimer une commande déjà livrée.'], 422);
        }

        DB::transaction(function () use ($commande) {
            // Reverse transit if still pending
            if ($commande->statut === 'en_attente') {
                foreach ($commande->lignesCommande as $ligne) {
                    $produit = $ligne->produit;
                    if ($produit) {
                        $produit->decrement('stock_en_transit', min($ligne->quantite, $produit->stock_en_transit));
                    }
                }
            }
            $commande->delete();
        });

        return response()->json(null, 204);
    }

    /**
     * Change statut (en_attente -> livree -> annulee)
     * On 'livree': transit → physique (stock_en_transit decremented, quantite incremented)
     * On 'annulee': transit reversed
     */
    public function updateStatut(Request $request, Commande $commande)
    {
        if (!$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'Seul un administrateur peut valider ou annuler une commande.'], 403);
        }

        $request->validate([
            'statut'               => 'required|in:en_attente,livree,annulee',
            'date_reception_reelle' => 'nullable|date',
        ]);

        if ($commande->statut === 'livree' || $commande->statut === 'annulee') {
            return response()->json(['message' => 'Statut terminal déjà atteint.'], 422);
        }

        DB::transaction(function () use ($request, $commande) {
            $updateData = ['statut' => $request->statut];

            if ($request->statut === 'livree') {
                $updateData['date_reception_reelle'] = $request->date_reception_reelle ?? now()->toDateString();
            }

            $commande->update($updateData);

            if ($request->statut === 'livree') {
                foreach ($commande->lignesCommande as $ligne) {
                    $produit = $ligne->produit;
                    if ($produit) {
                        // ── Transit → Stock Physique ──
                        $decrementTransit = min($ligne->quantite, $produit->stock_en_transit);
                        $produit->decrement('stock_en_transit', $decrementTransit);
                        $produit->increment('quantite', $ligne->quantite);
                        $produit->is_active = true;
                        $produit->save();

                        // Mouvement de stock Entrée
                        \App\Models\MouvementStock::create([
                            'produit_id'    => $produit->id,
                            'utilisateur_id' => $request->user()->id,
                            'type'          => 'entree',
                            'quantite'      => $ligne->quantite,
                            'date_mouvement' => now(),
                            'note'          => 'Réception Cmd #' . $commande->id,
                            'stock_apres'   => $produit->quantite,
                        ]);
                    }
                }
            } elseif ($request->statut === 'annulee') {
                // Reverse transit
                foreach ($commande->lignesCommande as $ligne) {
                    $produit = $ligne->produit;
                    if ($produit) {
                        $produit->decrement('stock_en_transit', min($ligne->quantite, $produit->stock_en_transit));
                    }
                }
            }
        });

        return new CommandeResource($commande->load('lignesCommande.produit'));
    }
}
