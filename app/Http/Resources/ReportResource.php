<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'utilisateur_id' => $this->utilisateur_id,
            'utilisateur' => new UserResource($this->whenLoaded('utilisateur')),
            'type' => $this->type,
            'chemin_fichier' => $this->chemin_fichier,
            'genere_le' => $this->genere_le,
            'download_url' => route('rapports.download', $this->id),
        ];
    }
}
