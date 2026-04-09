<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HistoriqueVentesResource;
use App\Models\HistoriqueVentes;
use Illuminate\Http\Request;

class HistoriqueVentesController extends Controller
{
    public function index(Request $request)
    {
        $query = HistoriqueVentes::query();
        if ($request->has('produit_id')) {
            $query->where('produit_id', $request->produit_id);
        }
        return HistoriqueVentesResource::collection($query->with('produit')->latest('date')->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'produit_id' => 'required|exists:produits,id',
            'date' => 'required|date',
            'quantite' => 'required|integer|min:1',
        ]);

        $history = HistoriqueVentes::create($validated);
        return new HistoriqueVentesResource($history);
    }
}
