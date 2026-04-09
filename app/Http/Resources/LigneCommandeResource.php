<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LigneCommandeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'produit_id' => $this->produit_id,
            'produit' => new ProductResource($this->whenLoaded('produit')),
            'quantite' => $this->quantite,
            'prix' => $this->prix,
            'total' => $this->quantite * $this->prix,
        ];
    }
}
