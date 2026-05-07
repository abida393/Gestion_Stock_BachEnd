<?php

namespace App\Services;

use App\Models\Produit;
use App\Models\Alerte;
use App\Models\Commande;
use App\Models\MouvementStock;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get aggregated KPIs for the dashboard.
     */
    public function getKPIs()
    {
        return [
            'total_products' => Produit::count(),
            'low_stock_count' => Produit::whereColumn('quantite', '<=', 'seuil_min')->count(),
            'active_alerts' => Alerte::where('est_active', true)->count(),
            'pending_orders' => Commande::where('statut', 'en_attente')->count(),
            'total_stock_value' => (float) Produit::select(DB::raw('SUM(quantite * prix) as value'))->value('value'),
            'movements_this_month' => MouvementStock::whereMonth('date_mouvement', now()->month)
                ->whereYear('date_mouvement', now()->year)
                ->when(!auth()->user()->hasRole('admin'), function ($query) {
                    return $query->where('utilisateur_id', auth()->id());
                })
                ->count(),
        ];
    }
}
