<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommandeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fournisseur_id' => $this->fournisseur_id,
            'fournisseur' => new FournisseurResource($this->whenLoaded('fournisseur')),
            'date_commande' => $this->date_commande,
            'statut' => $this->statut,
            'total' => $this->total,
            'lignes' => LigneCommandeResource::collection($this->whenLoaded('lignesCommande')),
            'created_at' => $this->created_at,
        ];
    }
}
