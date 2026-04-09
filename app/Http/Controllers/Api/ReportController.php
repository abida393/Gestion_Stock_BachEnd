<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\Models\Rapport;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Aggregated KPIs for dashboard
     */
    public function dashboard()
    {
        return response()->json($this->dashboardService->getKPIs());
    }

    public function index()
    {
        return ReportResource::collection(Rapport::with('utilisateur')->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'chemin_fichier' => 'required|string',
        ]);

        $report = Rapport::create([
            'utilisateur_id' => Auth::id(),
            'type' => $validated['type'],
            'chemin_fichier' => $validated['chemin_fichier'],
            'genere_le' => now(),
        ]);

        return new ReportResource($report);
    }

    public function download(Rapport $rapport)
    {
        if (!Storage::disk('public')->exists($rapport->chemin_fichier)) {
            abort(404, 'Fichier introuvable');
        }
        
        return Storage::disk('public')->download($rapport->chemin_fichier);
    }

    public function destroy(Rapport $rapport)
    {
        if (Storage::disk('public')->exists($rapport->chemin_fichier)) {
            Storage::disk('public')->delete($rapport->chemin_fichier);
        }
        $rapport->delete();
        return response()->json(null, 204);
    }
}
