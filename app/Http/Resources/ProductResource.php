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
            'stock_reserve' => (int) $this->stock_reserve,
            'stock_disponible' => (int) ($this->quantite - $this->stock_reserve),
            'categorie' => new CategoryResource($this->whenLoaded('categorie')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_low_stock' => $this->quantite <= $this->seuil_min,
            'fournisseurs' => $this->whenLoaded('fournisseurs', function () {
                return $this->fournisseurs->map(function ($f) {
                    return [
                        'id' => $f->id,
                        'nom' => $f->nom,
                        'email' => $f->email,
                        'telephone' => $f->telephone,
                        'pivot' => [
                            'prix_unitaire' => $f->pivot->prix_unitaire,
                            'delai_livraison_jours' => $f->pivot->delai_livraison_jours,
                        ]
                    ];
                });
            }),
            'mouvements' => $this->whenLoaded('mouvementStock', function () {
                return $this->mouvementStock->map(function ($m) {
                    return [
                        'id' => $m->id,
                        'type' => $m->type ?? $m->type_mouvement,
                        'quantite' => $m->quantite,
                        'date_mouvement' => $m->date_mouvement,
                        'note' => $m->note ?? $m->reference,
                        'stock_apres' => $m->stock_apres,
                        'utilisateur' => $m->utilisateur ? [
                            'id' => $m->utilisateur->id,
                            'nom' => $m->utilisateur->nom ?? $m->utilisateur->name,
                        ] : null,
                    ];
                });
            }),
            'pivot' => $this->whenPivotLoaded('produit_fournisseur', function () {
                return [
                    'prix_unitaire' => $this->pivot->prix_unitaire,
                    'delai_livraison_jours' => $this->pivot->delai_livraison_jours,
                ];
            }),

        ];
    }
}
