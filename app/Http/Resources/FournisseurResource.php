<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FournisseurResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'numero_fix' => $this->numero_fix,
            'adresse' => $this->adresse,
            'produits' => ProductResource::collection($this->whenLoaded('produits')),
            'cree_le' => $this->cree_le,
            'mis_a_jour_le' => $this->mis_a_jour_le,
        ];
    }
}
