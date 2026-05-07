<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FournisseurRequest;
use App\Http\Resources\FournisseurResource;
use App\Models\Fournisseur;
use Illuminate\Http\Request;

class FournisseurController extends Controller
{
    /**
     * List fournisseurs (search, filter)
     */
    public function index(Request $request)
    {
        $query = Fournisseur::query();
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('telephone', 'like', "%$search%");
            });
        }
        
        return FournisseurResource::collection($query->paginate(15));
    }

    /**
     * Create fournisseur
     */
    public function store(FournisseurRequest $request)
    {
        $data = $request->validated();
        $produits = $data['produits'] ?? null;
        unset($data['produits']);

        $fournisseur = Fournisseur::create($data);

        if ($produits) {
            $syncData = [];
            foreach ($produits as $p) {
                $syncData[$p['id']] = [
                    'prix_unitaire' => $p['prix_unitaire'],
                    'delai_livraison_jours' => $p['delai_livraison_jours']
                ];
            }
            $fournisseur->produits()->sync($syncData);
        }

        return new FournisseurResource($fournisseur->load('produits'));
    }

    /**
     * Get fournisseur details
     */
    public function show(Fournisseur $fournisseur)
    {
        return new FournisseurResource($fournisseur->load('produits'));
    }

    /**
     * Update fournisseur
     */
    public function update(FournisseurRequest $request, Fournisseur $fournisseur)
    {
        $data = $request->validated();
        $produits = $data['produits'] ?? null;
        unset($data['produits']);

        $fournisseur->update($data);

        if ($produits !== null) {
            $syncData = [];
            foreach ($produits as $p) {
                $syncData[$p['id']] = [
                    'prix_unitaire' => $p['prix_unitaire'],
                    'delai_livraison_jours' => $p['delai_livraison_jours']
                ];
            }
            $fournisseur->produits()->sync($syncData);
        }

        return new FournisseurResource($fournisseur->load('produits'));
    }

    /**
     * Soft delete fournisseur
     */
    public function destroy(Fournisseur $fournisseur)
    {
        $fournisseur->delete();
        return response()->json(null, 204);
    }
}
