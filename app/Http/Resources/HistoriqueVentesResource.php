<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoriqueVentesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'produit_id' => $this->produit_id,
            'produit' => new ProductResource($this->whenLoaded('produit')),
            'date' => $this->date,
            'quantite' => $this->quantite,
            'created_at' => $this->created_at,
        ];
    }
}
