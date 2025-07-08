<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Ordonnance;
use App\Models\OrdonnanceProduit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdonnanceController extends Controller
{
    public function index()
    {

    }
    public function HistoriqueOrdonnancesClient(Request $request, $client_id)
    {
        try {
            $perPage = $request->input('limit', 25);
            $page = $request->input('page', 1);

            // Vérifier si le client existe
            $client = Client::find($client_id);
            if (!$client) {
                return response()->json(['message' => 'Client non trouvé'], 404);
            }

            // Requête des ordonnances liées au client via les rapports de consultation
            $ordonnances = Ordonnance::whereHas('rapportConsultation.dossierConsultation.rendezVous', function ($query) use ($client_id) {
                $query->where('client_id', $client_id);
            })
                ->with([
                    'rapportConsultation.dossierConsultation.rendezVous.client',
                    'rapportConsultation.dossierConsultation.rendezVous.consultant',
                    'produits', // Produits de l'ordonnance
                    'creator',
                    'updater',
                ])
                ->orderByDesc('created_at')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data' => $ordonnances->items(),
                'current_page' => $ordonnances->currentPage(),
                'last_page' => $ordonnances->lastPage(),
                'total' => $ordonnances->total(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des ordonnances.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission OrdonnanceController::store
     * @permission_desc Enregistrer des ordonnances pour des rapports de consultations
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $request->validate([
            'rapport_consultations_id' => 'nullable|exists:ops_tbl_rapport_consultations,id',
            'description' => 'nullable|string',
            'produits' => 'required|array|min:1',
            'produits.*.nom' => 'required|string',
            'produits.*.quantite' => 'required|integer|min:1',
            'produits.*.protocole' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $ordonnance = Ordonnance::create([
                'rapport_consultations_id' => $request->rapport_consultations_id,
                'description' => $request->description,
                'created_by' => $auth->id
            ]);

            foreach ($request->produits as $produit) {
                OrdonnanceProduit::create([
                    'ordonnance_id' => $ordonnance->id,
                    'nom' => $produit['nom'],
                    'quantite' => $produit['quantite'],
                    'protocole' => $produit['protocole'],
                    'created_by' => $auth->id
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Ordonnance et produits enregistrés avec succès',
                'data' => $ordonnance->load('produits')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de l’enregistrement',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     * @permission OrdonnanceController::update
     * @permission_desc Modifier des ordonnances pour des rapports de consultations
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();
        $ordonnance = Ordonnance::findOrFail($id);

        $validated = $request->validate([
            'rapport_consultations_id' => 'nullable|exists:ops_tbl_rapport_consultations,id',
            'description' => 'nullable|string',
        ]);

        $ordonnance->update([
            'rapport_consultations_id' => $validated['rapport_consultations_id'] ?? $ordonnance->rapport_consultations_id,
            'description' => $validated['description'] ?? $ordonnance->description,
            'updated_by' => $auth->id
        ]);

        return response()->json(['message' => 'Ordonnance mise à jour avec succès', 'data' => $ordonnance]);
    }
    //
}
