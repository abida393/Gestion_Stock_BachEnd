<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductRequest;
use App\Models\Produit;
use Illuminate\Http\Request;

class ProductRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductRequest::with(['user', 'categorie'])->latest();

        if (!$request->user()->hasRole('admin')) {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'categorie_id' => 'nullable|exists:categories,id',
        ]);

        $productRequest = ProductRequest::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'status' => 'en_attente',
        ]);

        return response()->json($productRequest->load(['user', 'categorie']), 201);
    }

    public function updateStatus(Request $request, ProductRequest $productRequest)
    {
        $this->authorize('updateStatus', $productRequest); // or just check admin

        if (!$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:approuve,rejete',
            'prix' => 'nullable|numeric|min:0',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
            'quantite_commande' => 'nullable|integer|min:0',
        ]);

        $productRequest->update(['status' => $validated['status']]);

        // Create product if approved
        if ($validated['status'] === 'approuve') {
            $hasOrder = !empty($validated['quantite_commande']) && $validated['quantite_commande'] > 0 && !empty($validated['fournisseur_id']);
            
            $produit = Produit::create([
                'nom' => $productRequest->nom,
                'sku' => 'PRD-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                'description' => $productRequest->description,
                'categorie_id' => $productRequest->categorie_id,
                'quantite' => 0,
                'is_active' => $hasOrder ? false : true,
                'seuil_min' => 10,
                'prix' => $validated['prix'] ?? 0,
            ]);

            if (!empty($validated['fournisseur_id'])) {
                $produit->fournisseurs()->attach($validated['fournisseur_id'], [
                    'prix_unitaire' => $validated['prix'] ?? 0, 
                    'delai_livraison_jours' => 3
                ]);
            }

            if (!empty($validated['quantite_commande']) && $validated['quantite_commande'] > 0 && !empty($validated['fournisseur_id'])) {
                $commande = \App\Models\Commande::create([
                    'fournisseur_id' => $validated['fournisseur_id'],
                    'date_commande' => now(),
                    'statut' => 'en_attente',
                    'total' => ($validated['prix'] ?? 0) * $validated['quantite_commande'],
                ]);
                
                \App\Models\LigneCommande::create([
                    'commande_id' => $commande->id,
                    'produit_id' => $produit->id,
                    'quantite' => $validated['quantite_commande'],
                    'prix' => $validated['prix'] ?? 0,
                ]);
            }
        }

        return response()->json($productRequest->load(['user', 'categorie']));
    }
}
