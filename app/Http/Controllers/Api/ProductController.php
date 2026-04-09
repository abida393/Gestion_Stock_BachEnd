<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * List products (filter: categorie, seuil_min, search)
     */
    public function index(Request $request)
    {
        $query = Produit::query();

        if ($request->has('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }

        if ($request->has('seuil_min_only')) {
            $query->whereColumn('quantite', '<=', 'seuil_min');
        }

        if ($request->has('search')) {
            $query->where('nom', 'like', '%' . $request->search . '%');
        }

        return ProductResource::collection($query->with('categorie')->paginate(15));
    }

    /**
     * Create product
     */
    public function store(ProductRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Produit::create($data);
        return new ProductResource($product);
    }

    /**
     * Get product details
     */
    public function show(Produit $product)
    {
        return new ProductResource($product->load('categorie'));
    }

    /**
     * Update product
     */
    public function update(ProductRequest $request, Produit $product)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);
        return new ProductResource($product);
    }

    /**
     * Soft delete product
     */
    public function destroy(Produit $product)
    {
        $product->delete();
        return response()->json(null, 204);
    }

    /**
     * Upload product image separately
     */
    public function uploadImage(Request $request, Produit $product)
    {
        $request->validate([
            'image' => 'required|image|max:2048',
        ]);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $path = $request->file('image')->store('products', 'public');
        $product->update(['image' => $path]);

        return new ProductResource($product);
    }
}
