<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MouvementStockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'produit_id' => $this->produit_id,
            'produit' => new ProductResource($this->whenLoaded('produit')),
            'utilisateur_id' => $this->utilisateur_id,
            'utilisateur' => new UserResource($this->whenLoaded('utilisateur')),
            'quantite' => $this->quantite,
            'stock_apres' => $this->stock_apres,
            'note' => $this->note,
            'date_mouvement' => $this->date_mouvement,
            'type' => $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
