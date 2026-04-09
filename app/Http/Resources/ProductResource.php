<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'quantite' => $this->quantite,
            'seuil_min' => $this->seuil_min,
            'image_url' => $this->image ? Storage::url($this->image) : null,
            'prix' => $this->prix,
            'categorie' => new CategoryResource($this->whenLoaded('categorie')),
            'cree_le' => $this->cree_le,
            'mis_a_jour_le' => $this->mis_a_jour_le,
            'is_low_stock' => $this->quantite <= $this->seuil_min,
        ];
    }
}
