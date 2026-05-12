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
            'sku' => $this->sku,
            'description' => $this->description,
            'quantite' => $this->quantite,
            'seuil_min' => $this->seuil_min,
            'image_url' => $this->image ? Storage::url($this->image) : null,
            'prix' => $this->prix,
            'categorie' => new CategoryResource($this->whenLoaded('categorie')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_low_stock' => $this->quantite <= $this->seuil_min,
            'pivot' => $this->whenPivotLoaded('produit_fournisseur', function () {
                return [
                    'prix_unitaire' => $this->pivot->prix_unitaire,
                    'delai_livraison_jours' => $this->pivot->delai_livraison_jours,
                ];
            }),

        ];
    }
}
