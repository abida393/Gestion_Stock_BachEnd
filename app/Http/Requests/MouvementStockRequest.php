<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MouvementStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'produit_id' => 'required|exists:produits,id',
            'quantite' => 'required|integer|min:1',
            'type' => 'required|in:entree,sortie',
            'note' => 'nullable|string',
            'date_mouvement' => 'required|date',
        ];
    }
}
