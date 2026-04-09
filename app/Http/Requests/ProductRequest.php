<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
            'quantite' => 'required|integer',
            'seuil_min' => 'required|integer',
            'categorie_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|max:2048',
            'prix' => 'required|numeric|min:0',
        ];
    }
}
