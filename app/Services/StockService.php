<?php

namespace App\Services;

use App\Models\Produit;
use App\Models\MouvementStock;
use App\Models\Alerte;
use App\Models\Notification;
use App\Services\AIService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        try {
            Log::info('StockService: Recording movement', $data);
            $product = Produit::findOrFail($data['produit_id']);

        // ── Validation IA en temps réel ──
        $aiValidation = $this->aiService->validateMovement($product, $data['type'], $data['quantite']);
        if (!$aiValidation['allowed']) {
            abort(400, "🚫 ACTION BLOQUÉE PAR L'IA : " . $aiValidation['reason']);
        }

        return DB::transaction(function () use ($data, $product) {
            $product->lockForUpdate();
            
            $oldStock = $product->quantite;
            $movementQty = $data['quantite'];
            $newStock = $oldStock;
            $stockReserve = $product->stock_reserve;
            
            if ($data['type'] === 'entree') {
                $newStock = $oldStock + $movementQty;
                $product->update(['quantite' => $newStock]);
            } elseif ($data['type'] === 'sortie') {
                if ($product->stock_disponible <= 0) {
                    throw new \Exception("Opération impossible : Le stock disponible est de 0.");
                }
                if ($product->stock_disponible < $movementQty) {
                    throw new \Exception("Stock disponible insuffisant. Disponible : {$product->stock_disponible}.");
                }
                $newStock = $oldStock - $movementQty;
                $product->update(['quantite' => $newStock]);
            } elseif ($data['type'] === 'reservation') {
                if ($product->stock_disponible < $movementQty) {
                    throw new \Exception("Stock disponible insuffisant pour cette réservation. Disponible : {$product->stock_disponible}.");
                }
                $stockReserve += $movementQty;
                $product->update(['stock_reserve' => $stockReserve]);
            } elseif ($data['type'] === 'annulation_reservation') {
                if ($stockReserve < $movementQty) {
                    throw new \Exception("Impossible d'annuler plus que ce qui est réservé.");
                }
                $stockReserve -= $movementQty;
                $product->update(['stock_reserve' => $stockReserve]);
            }

            // Record movement
            $movement = MouvementStock::create([
                'produit_id' => $product->id,
                'utilisateur_id' => Auth::id(),
                'quantite' => $movementQty,
                'stock_apres' => $newStock,
                'type' => $data['type'],
                'note' => $data['note'] ?? null,
                'date_mouvement' => $data['date_mouvement'],
                'date_expiration' => $data['date_expiration'] ?? null,
                'region' => $data['region'] ?? null,
                'canal' => $data['canal'] ?? null,
                'prix_unitaire' => $product->prix,
            ]);

            // Check alerts
            $this->checkAlerts($product);

            Log::info('StockService: Movement recorded successfully', ['id' => $movement->id]);
            return $movement;
        });
        } catch (\Exception $e) {
            Log::error('StockService: Movement failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Check if product stock level triggers any alert rules.
     */
    protected function checkAlerts(Produit $product)
    {
        if ($product->stock_disponible <= $product->seuil_min) {
            // Trigger or update alert
            $alert = Alerte::firstOrCreate(
                ['produit_id' => $product->id, 'est_active' => true],
                ['seuil' => $product->seuil_min, 'declenche_le' => now()]
            );

            // Create notification for users
            Notification::create([
                'utilisateur_id' => Auth::id(), // Or notify admins specifically
                'message' => "Stock disponible bas pour le produit: {$product->nom} (Disponible: {$product->stock_disponible}, Physique: {$product->quantite})",
                'type' => 'warning',
                'lu' => false,
            ]);
        }
    }
}
