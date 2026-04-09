<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PrevisionResource;
use App\Models\Prevision;
use Illuminate\Http\Request;

class PrevisionController extends Controller
{
    public function index(Request $request)
    {
        $query = Prevision::query();
        if ($request->has('produit_id')) {
            $query->where('produit_id', $request->produit_id);
        }
        return PrevisionResource::collection($query->with('produit')->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'produit_id' => 'required|exists:produits,id',
            'periode' => 'required|string',
            'quantite_predite' => 'required|numeric',
            'confiance' => 'required|numeric|between:0,1',
            'eoq' => 'nullable|numeric',
            'score_anomalie' => 'nullable|numeric',
        ]);

        $prevision = Prevision::create($validated);
        return new PrevisionResource($prevision);
    }

    public function show(Prevision $prevision)
    {
        return new PrevisionResource($prevision->load('produit'));
    }

    public function destroy(Prevision $prevision)
    {
        $prevision->delete();
        return response()->json(null, 204);
    }
}
