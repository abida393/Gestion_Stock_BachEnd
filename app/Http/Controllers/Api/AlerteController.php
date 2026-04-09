<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlerteResource;
use App\Models\Alerte;
use Illuminate\Http\Request;

class AlerteController extends Controller
{
    /**
     * List alerts (filter: est_active, produit)
     */
    public function index(Request $request)
    {
        $query = Alerte::query();
        
        if ($request->has('est_active')) {
            $query->where('est_active', $request->boolean('est_active'));
        }
        
        if ($request->has('produit_id')) {
            $query->where('produit_id', $request->produit_id);
        }
        
        return AlerteResource::collection(
            $query->with('produit')->latest('declenche_le')->paginate(15)
        );
    }

    /**
     * Get alert details
     */
    public function show(Alerte $alerte)
    {
        return new AlerteResource($alerte->load('produit'));
    }

    /**
     * Mark alert as resolved (resolu_le)
     */
    public function resolve(Alerte $alerte)
    {
        $alerte->update([
            'est_active' => false,
            'resolu_le' => now(),
        ]);
        
        return new AlerteResource($alerte);
    }

    /**
     * Active unresolved alerts only
     */
    public function actives()
    {
        return AlerteResource::collection(
            Alerte::where('est_active', true)->with('produit')->latest('declenche_le')->get()
        );
    }

    /**
     * Delete alert
     */
    public function destroy(Alerte $alerte)
    {
        $alerte->delete();
        return response()->json(null, 204);
    }
}
