<?php

namespace App\Http\Controllers;

use App\Models\BilanActeRendezVous;
use App\Models\Client;
use App\Models\OpsTblRapportConsultation;
use App\Models\Ordonnance;
use App\Models\OrdonnanceProduit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @permission_category Gestion des ordonnances
 */
class OrdonnanceController extends Controller
{
    public function index()
    {

    }
    /**
     * Display a listing of the resource.
     * @permission OpsTblReferreMedicalController::historiqueReferresMedicaux
     * @permission_desc Afficher l'historique des referres médicals d'un client
     */
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

            $rapport = optional($ordonnance->rapportConsultation);
            $client = optional($rapport->dossierConsultation->rendezVous->client);
            $consultant = optional($rapport->dossierConsultation->rendezVous->consultant);

            // Préparer les données pour le PDF
            $data = [
                'ordonnance' => $ordonnance->load('produits'),
                'consultant' => $consultant->nomcomplet,
                'patient' => $client->nomcomplet_client ?? '...........................',
                'date_aujourdhui' => now()->format('d/m/Y'),
            ];

            $fileName = 'ordonnance-' . now()->format('YmdHis') . '.pdf';
            $folderPath = 'storage/ordonnances';
            $filePath = $folderPath . '/' . $fileName;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            // Génération du PDF avec format A5 et marges élargies
            save_browser_shot_pdf(
                view: 'pdfs.ordonnances.ordonnance',
                data: ['data' => $data],
                folderPath: $folderPath,
                path: $filePath,
                margins: [15, 10, 15, 10],
                format: 'A5',
                direction: 'portrait'
            );

            DB::commit();

            $pdfContent = file_get_contents($filePath);
            $base64 = base64_encode($pdfContent);

            return response()->json([
                'message' => 'Ordonnance enregistrée avec succès.',
                'data' => $ordonnance->load('produits'),
                'base64' => $base64,
                'url' => asset($filePath),
                'filename' => $fileName,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de l’enregistrement',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function printFromRapport(Request $request,int $rapport_consultation_id)
    {
        DB::beginTransaction();

        try {
            // Récupère le rapport avec ordonnance
            $rapport = OpsTblRapportConsultation::with(['ordonnance.produits', 'dossierConsultation.rendezVous.client'])
                ->findOrFail($rapport_consultation_id);

            $ordonnance = $rapport->ordonnance;

            if (!$ordonnance) {
                return response()->json(['message' => 'Aucune ordonnance associée.'], 404);
            }

            $client = $rapport->dossierConsultation->rendezVous->client ?? null;

            $data = [
                'ordonnance' => $ordonnance,
                'produits' => $ordonnance->produits,
                'client' => $client,
            ];

            $fileName   = 'ordonnance-client-' . $ordonnance->id . '-' . now()->format('YmdHis') . '.pdf';
            $folderPath = 'storage/ordonnances';
            $filePath   = $folderPath . '/' . $fileName;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            save_browser_shot_pdf(
                view: 'pdfs.ordonnances.ordonnance',
                data: $data,
                folderPath: $folderPath,
                path: $filePath,
                margins: [15, 10, 15, 10]
            );

            DB::commit();

            if (!file_exists($filePath)) {
                return response()->json(['message' => 'Le fichier PDF n\'a pas été généré.'], 500);
            }
            $pdfContent = file_get_contents($filePath);
            $base64 = base64_encode($pdfContent);

            return response()->json([
                'ordonnance' => $ordonnance,
                'base64'   => $base64,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erreur génération PDF ordonnance via rapport : ' . $e->getMessage());

            return response()->json([
                'message' => 'Erreur lors de la génération.',
                'error' => config('app.debug') ? $e->getMessage() : null,
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
