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
            'type' => 'required|in:entree,sortie,reservation,annulation_reservation',
            'note' => 'nullable|string',
            'date_mouvement' => 'required|date',
            'date_expiration' => 'nullable|date|after_or_equal:today',
            'region' => 'nullable|string',
            'canal' => 'nullable|string',
        ];
    }
}
