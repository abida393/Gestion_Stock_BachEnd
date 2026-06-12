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
    public function getKPIs($request = null)
    {
        return [
            'total_products'           => Produit::count(),
            'stock_physique_total'      => (int) Produit::sum('quantite'),
            'stock_en_transit_total'    => (int) Produit::sum('stock_en_transit'),
            'stock_disponible_total'    => (int) Produit::sum(DB::raw('(quantite + stock_en_transit) - stock_reserve')),
            'stock_reserve_total'       => (int) Produit::sum('stock_reserve'),
            'low_stock_count' => Produit::whereRaw('(quantite + stock_en_transit - stock_reserve) <= seuil_min')->count(),
            'active_alerts' => Alerte::where('est_active', true)->count(),
            'pending_orders' => Commande::where('statut', 'en_attente')->count(),
            'total_stock_value' => (float) Produit::select(DB::raw('SUM(quantite * prix) as value'))->value('value'),
            'movements_this_month' => MouvementStock::whereMonth('date_mouvement', now()->month)
                ->whereYear('date_mouvement', now()->year)
                ->when(!auth()->user()->hasRole('admin'), function ($query) {
                    return $query->where('utilisateur_id', auth()->id());
                })
                ->count(),
            'top_users' => \App\Models\AuditLog::select('user_id', DB::raw('count(*) as total'))
                ->with('user:id,name')
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->orderByDesc('total')
                ->take(5)
                ->get(),
            'top_suppliers' => \App\Models\Fournisseur::withCount('commandes')
                ->with(['commandes' => function ($q) {
                    $q->whereNotNull('date_prevue_livraison')
                      ->whereNotNull('date_reception_reelle')
                      ->select('id', 'fournisseur_id', 'date_prevue_livraison', 'date_reception_reelle');
                }])
                ->orderByDesc('commandes_count')
                ->take(5)
                ->get(['id', 'nom', 'commandes_count'])
                ->map(function ($f) {
                    $rated = $f->commandes;
                    if ($rated->isEmpty()) {
                        $rating = 'N/A';
                        $avgDelay = null;
                    } else {
                        $delays = $rated->map(fn($c) =>
                            $c->date_reception_reelle->diffInDays($c->date_prevue_livraison, false)
                        );
                        $avgDelay = round($delays->avg(), 1);
                        if ($avgDelay <= 1)      $rating = 'A';
                        elseif ($avgDelay <= 5)  $rating = 'B';
                        else                     $rating = 'C';
                    }
                    return [
                        'id'              => $f->id,
                        'nom'             => $f->nom,
                        'commandes_count' => $f->commandes_count,
                        'rating'          => $rating,
                        'avg_delay_days'  => $avgDelay,
                    ];
                }),
            'sales_by_region' => MouvementStock::where('type', 'sortie')
                ->whereNotNull('region')
                ->select('region', 'canal', DB::raw('SUM(quantite * prix_unitaire) as revenue'))
                ->groupBy('region', 'canal')
                ->get(),
            'top_sales_qty' => MouvementStock::where('type', 'sortie')
                ->select('produit_id', DB::raw('SUM(quantite) as total_qty'))
                ->groupBy('produit_id')
                ->with('produit:id,nom')
                ->orderByDesc('total_qty')
                ->take(5)
                ->get(),
            'top_sales_value' => MouvementStock::where('type', 'sortie')
                ->select('produit_id', DB::raw('SUM(quantite * prix_unitaire) as total_value'))
                ->groupBy('produit_id')
                ->with('produit:id,nom')
                ->orderByDesc('total_value')
                ->take(5)
                ->get(),
            'next_reservation_expiration' => MouvementStock::where('type', 'reservation')
                ->where('date_expiration', '>=', now()->startOfDay())
                ->orderBy('date_expiration')
                ->value('date_expiration'),
            'ai_suggestions' => Produit::whereRaw('(quantite - stock_reserve) <= seuil_min')
                ->whereDoesntHave('lignesCommande', function($q) {
                    $q->whereHas('commande', function($sq) {
                        $sq->where('statut', 'en_attente');
                    });
                })
                ->orderBy(DB::raw('quantite - stock_reserve'))
                ->with('fournisseurs')
                ->take(1)
                ->get()
                ->map(function ($p) {
                    $available = $p->quantite - $p->stock_reserve;
                    $supplier = $p->fournisseurs->first();
                    return [
                        'id' => $p->id,
                        'nom' => $p->nom,
                        'prix' => $p->prix,
                        'eoq' => max(50, ($p->seuil_min * 2) - $available),
                        'fournisseur_id' => $supplier ? $supplier->id : null,
                        'fournisseur_nom' => $supplier ? $supplier->nom : 'Fournisseur inconnu'
                    ];
                })->first(),
            'low_stock_list' => Produit::whereRaw('(quantite + stock_en_transit - stock_reserve) <= seuil_min')
                ->orderByRaw('quantite + stock_en_transit - stock_reserve')
                ->take(5)
                ->get(['id', 'nom', 'quantite', 'seuil_min', 'stock_reserve', 'stock_en_transit']),
            'upcoming_orders' => Commande::whereIn('statut', ['en_attente', 'en_transit'])
                ->whereNotNull('date_prevue_livraison')
                ->with('fournisseur:id,nom')
                ->orderBy('date_prevue_livraison')
                ->take(10)
                ->get()
                ->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'reference' => $c->reference,
                        'fournisseur_nom' => $c->fournisseur->nom ?? 'Inconnu',
                        'date_livraison' => $c->date_prevue_livraison->format('Y-m-d'),
                        'statut' => $c->statut,
                    ];
                }),
            'upcoming_liberations' => MouvementStock::where('type', 'reservation')
                ->where('date_expiration', '>=', now()->startOfDay())
                ->with('produit:id,nom', 'utilisateur:id,name')
                ->orderBy('date_expiration')
                ->take(10)
                ->get()
                ->map(function ($m) {
                    return [
                        'id' => $m->id,
                        'produit_nom' => $m->produit->nom ?? 'Inconnu',
                        'user_name' => $m->utilisateur->name ?? 'Système',
                        'date_expiration' => $m->date_expiration->format('Y-m-d'),
                    ];
                }),
            'stock_history' => $this->getStockHistory($request),
        ];
    }

    private function getStockHistory($request = null)
    {
        $currentStock = (int) Produit::sum('quantite');
        $history = [];
        $days = 30;
        if ($request && $request->has('days')) {
            $days = (int) $request->input('days');
        }

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->endOfDay();
            $label = $date->translatedFormat('D'); // e.g. "Lun"
            
            // Get movements that happened AFTER this date to reverse them
            $netMovementsAfter = MouvementStock::where('cree_le', '>', $date)
                ->select(DB::raw("SUM(CASE WHEN type = 'entree' THEN quantite ELSE -quantite END) as net"))
                ->value('net') ?? 0;

            $history[] = [
                'day' => strtoupper($label),
                'value' => max(0, $currentStock - $netMovementsAfter)
            ];
        }

        return array_reverse($history);
    }
}
