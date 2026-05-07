<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FournisseurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telephone' => 'required|string|max:20',
            'numero_fix' => 'nullable|string|max:20',
            'adresse' => 'required|string',
            'produits' => 'nullable|array',
            'produits.*.id' => 'required|exists:produits,id',
            'produits.*.prix_unitaire' => 'required|numeric|min:0',
            'produits.*.delai_livraison_jours' => 'required|integer|min:0',
        ];
    }
}
