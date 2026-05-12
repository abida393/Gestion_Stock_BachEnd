<?php

namespace App\Services;

use App\Models\Produit;
use App\Models\MouvementStock;
use App\Models\Alerte;
use App\Models\Notification;
use App\Services\AIService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    protected $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Record a stock movement and update product quantity.
     */
    public function recordMovement(array $data)
    {
        $product = Produit::findOrFail($data['produit_id']);

        // ── Validation IA en temps réel ──
        $aiValidation = $this->aiService->validateMovement($product, $data['type'], $data['quantite']);
        if (!$aiValidation['allowed']) {
            throw new \Exception("🚫 ACTION BLOQUÉE PAR L'IA : " . $aiValidation['reason']);
        }

        return DB::transaction(function () use ($data, $product) {
            $product->lockForUpdate();
            
            $oldStock = $product->quantite;
            $movementQty = $data['quantite'];
            
            if ($data['type'] === 'entree') {
                $newStock = $oldStock + $movementQty;
            } else {
                if ($oldStock <= 0) {
                    throw new \Exception("Opération impossible : Le stock actuel est de 0.");
                }
                if ($oldStock < $movementQty) {
                    throw new \Exception("Stock insuffisant. Stock actuel : $oldStock.");
                }
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
