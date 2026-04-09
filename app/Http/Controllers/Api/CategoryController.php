<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Categorie;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * List all categories
     */
    public function index()
    {
        return CategoryResource::collection(Categorie::withCount('produits')->get());
    }

    /**
     * Create category
     */
    public function store(CategoryRequest $request)
    {
        $category = Categorie::create($request->validated());
        return new CategoryResource($category);
    }

    /**
     * Get category with products
     */
    public function show(Categorie $category)
    {
        return new CategoryResource($category->load('produits'));
    }

    /**
     * Update category
     */
    public function update(CategoryRequest $request, Categorie $category)
    {
        $category->update($request->validated());
        return new CategoryResource($category);
    }

    /**
     * Delete category
     */
    public function destroy(Categorie $category)
    {
        $category->delete();
        return response()->json(null, 204);
    }
}
