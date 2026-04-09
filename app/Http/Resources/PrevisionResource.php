<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrevisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'produit_id' => $this->produit_id,
            'produit' => new ProductResource($this->whenLoaded('produit')),
            'periode' => $this->periode,
            'quantite_predite' => $this->quantite_predite,
            'confiance' => $this->confiance,
            'eoq' => $this->eoq,
            'score_anomalie' => $this->score_anomalie,
            'created_at' => $this->created_at,
        ];
    }
}
