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
        $fournisseur = Fournisseur::create($request->validated());
        return new FournisseurResource($fournisseur);
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
        $fournisseur->update($request->validated());
        return new FournisseurResource($fournisseur);
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
