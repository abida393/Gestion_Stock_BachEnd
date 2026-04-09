<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MouvementStockRequest;
use App\Http\Resources\MouvementStockResource;
use App\Models\MouvementStock;
use App\Services\StockService;
use Illuminate\Http\Request;

class MouvementStockController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * List movements (filter: type, produit, date range)
     */
    public function index(Request $request)
    {
        $query = MouvementStock::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('produit_id')) {
            $query->where('produit_id', $request->produit_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date_mouvement', [$request->start_date, $request->end_date]);
        }

        return MouvementStockResource::collection(
            $query->with(['produit', 'utilisateur'])->latest('date_mouvement')->paginate(15)
        );
    }

    /**
     * Record stock movement (entree/sortie)
     */
    public function store(MouvementStockRequest $request)
    {
        $movement = $this->stockService->recordMovement($request->validated());
        return new MouvementStockResource($movement->load(['produit', 'utilisateur']));
    }

    /**
     * Get movement details
     */
    public function show(MouvementStock $mouvement)
    {
        return new MouvementStockResource($mouvement->load(['produit', 'utilisateur']));
    }

    /**
     * Filter: type=entree only
     */
    public function entries()
    {
        return MouvementStockResource::collection(
            MouvementStock::where('type', 'entree')->with(['produit', 'utilisateur'])->paginate(15)
        );
    }

    /**
     * Filter: type=sortie only
     */
    public function exits()
    {
        return MouvementStockResource::collection(
            MouvementStock::where('type', 'sortie')->with(['produit', 'utilisateur'])->paginate(15)
        );
    }
}
