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
        $query = Produit::query()->where('is_active', true);

        if ($request->has('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }

        if ($request->has('seuil_min_only') || $request->is('*/low-stock')) {
            $query->whereColumn('quantite', '<=', 'seuil_min');
        }

        if ($request->has('status')) {
            $status = $request->status;
            if ($status === 'out') {
                $query->where('quantite', '<=', 0);
            } elseif ($status === 'low') {
                $query->whereColumn('quantite', '<=', 'seuil_min')->where('quantite', '>', 0);
            } elseif ($status === 'normal') {
                $query->whereColumn('quantite', '>', 'seuil_min');
            } elseif ($status === 'reserved') {
                $query->where('stock_reserve', '>', 0);
            }
        }

        if ($request->has('search')) {
            $query->whereRaw('LOWER(nom) LIKE ?', ['%' . strtolower($request->search) . '%']);
        }

        return ProductResource::collection($query->with('categorie')->paginate($request->input('per_page', 15)));
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

    public function show(Produit $product)
    {
        return new ProductResource($product->load(['categorie', 'fournisseurs', 'mouvementStock.utilisateur']));
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

    /**
     * Export products as CSV
     */
    public function export(Request $request)
    {
        $query = Produit::query()->where('is_active', true)->with('categorie');

        if ($request->has('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }
        if ($request->has('status')) {
            $status = $request->status;
            if ($status === 'low') {
                $query->whereColumn('quantite', '<=', 'seuil_min')->where('quantite', '>', 0);
            } elseif ($status === 'out') {
                $query->where('quantite', '<=', 0);
            } elseif ($status === 'reserved') {
                $query->where('stock_reserve', '>', 0);
            }
        }

        $products = $query->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="inventaire_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($products) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, [
                'SKU', 'Nom', 'Catégorie', 'Prix (€)',
                'Stock Physique', 'En Transit', 'Réservé', 'Disponible',
                'Seuil Minimum', 'Statut',
            ], ';');

            foreach ($products as $p) {
                $disponible = ($p->quantite + $p->stock_en_transit) - $p->stock_reserve;
                if ($disponible <= 0) {
                    $statut = 'Rupture';
                } elseif ($disponible <= $p->seuil_min) {
                    $statut = 'Stock Faible';
                } else {
                    $statut = 'Normal';
                }

                fputcsv($handle, [
                    $p->sku ?? '—',
                    $p->nom,
                    $p->categorie->nom ?? '—',
                    number_format($p->prix, 2, '.', ''),
                    $p->quantite,
                    $p->stock_en_transit ?? 0,
                    $p->stock_reserve,
                    $disponible,
                    $p->seuil_min,
                    $statut,
                ], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}

