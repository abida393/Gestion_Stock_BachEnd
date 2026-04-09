<?php

namespace App\Services;

use App\Models\Produit;
use App\Models\MouvementStock;
use App\Models\Alerte;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Record a stock movement and update product quantity.
     */
    public function recordMovement(array $data)
    {
        return DB::transaction(function () use ($data) {
            $product = Produit::lockForUpdate()->findOrFail($data['produit_id']);
            
            $oldStock = $product->quantite;
            $movementQty = $data['quantite'];
            
            if ($data['type'] === 'entree') {
                $newStock = $oldStock + $movementQty;
            } else {
                $newStock = $oldStock - $movementQty;
            }

            // Update product stock
            $product->update(['quantite' => $newStock]);

            // Record movement
            $movement = MouvementStock::create([
                'produit_id' => $product->id,
                'utilisateur_id' => Auth::id(),
                'quantite' => $movementQty,
                'stock_apres' => $newStock,
                'type' => $data['type'],
                'note' => $data['note'] ?? null,
                'date_mouvement' => $data['date_mouvement'],
            ]);

            // Check alerts
            $this->checkAlerts($product);

            return $movement;
        });
    }

    /**
     * Check if product stock level triggers any alert rules.
     */
    protected function checkAlerts(Produit $product)
    {
        if ($product->quantite <= $product->seuil_min) {
            // Trigger or update alert
            $alert = Alerte::firstOrCreate(
                ['produit_id' => $product->id, 'est_active' => true],
                ['seuil' => $product->seuil_min, 'declenche_le' => now()]
            );

            // Create notification for users
            Notification::create([
                'utilisateur_id' => Auth::id(), // Or notify admins specifically
                'message' => "Stock bas pour le produit: {$product->nom} (Quantité: {$product->quantite})",
                'type' => 'warning',
                'lu' => false,
            ]);
        }
    }
}
