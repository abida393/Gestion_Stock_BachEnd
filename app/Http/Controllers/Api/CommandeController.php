<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommandeRequest;
use App\Http\Resources\CommandeResource;
use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommandeController extends Controller
{
    /**
     * List all orders (filter: statut, fournisseur, date)
     */
    public function index(Request $request)
    {
        $query = Commande::query();
        
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
     * Create order with lines
     */
    public function store(CommandeRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $commande = Commande::create([
                'fournisseur_id' => $request->fournisseur_id,
                'date_commande' => $request->date_commande,
                'statut' => 'en_attente',
                'total' => collect($request->lignes)->sum(fn($l) => $l['quantite'] * $l['prix']),
            ]);

            foreach ($request->lignes as $ligne) {
                $commande->lignesCommande()->create($ligne);
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
            $commande->update([
                'fournisseur_id' => $request->fournisseur_id,
                'date_commande' => $request->date_commande,
                'total' => collect($request->lignes)->sum(fn($l) => $l['quantite'] * $l['prix']),
            ]);

            $commande->lignesCommande()->delete();
            foreach ($request->lignes as $ligne) {
                $commande->lignesCommande()->create($ligne);
            }

            return new CommandeResource($commande->load('lignesCommande'));
        });
    }

    /**
     * Cancel / delete order
     */
    public function destroy(Commande $commande)
    {
        if ($commande->statut === 'livree') {
            return response()->json(['message' => 'Impossible de supprimer une commande déjà livrée.'], 422);
        }
        
        $commande->delete();
        return response()->json(null, 204);
    }

    /**
     * Change statut (en_attente -> livree -> annulee)
     */
    public function updateStatut(Request $request, Commande $commande)
    {
        $request->validate([
            'statut' => 'required|in:en_attente,livree,annulee',
        ]);

        if ($commande->statut === 'livree' || $commande->statut === 'annulee') {
            return response()->json(['message' => 'Statut terminal déjà atteint.'], 422);
        }

        $commande->update(['statut' => $request->statut]);

        return new CommandeResource($commande);
    }
}
