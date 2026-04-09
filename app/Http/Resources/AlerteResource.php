<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlerteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'produit_id' => $this->produit_id,
            'produit' => new ProductResource($this->whenLoaded('produit')),
            'seuil' => $this->seuil,
            'est_active' => (bool)$this->est_active,
            'declenche_le' => $this->declenche_le,
            'resolu_le' => $this->resolu_le,
            'cree_le' => $this->cree_le,
        ];
    }
}
