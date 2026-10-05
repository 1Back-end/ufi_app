<?php

namespace App\Http\Controllers;

use App\Models\BilanActeRendezVous;
use App\Models\Centre;
use App\Models\Client;
use App\Models\OpsTblRapportConsultation;
use App\Models\Ordonnance;
use App\Models\OrdonnanceProduit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @permission_category Gestion des ordonnances
 * @permission_module Gestion des prestations
 */
class OrdonnanceController extends Controller
{

    /**
     * Display a listing of the resource.
     * @permission OrdonnanceController::store
     * @permission_desc Enregistrer des ordonnances pour des rapports de consultations
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => 'Centre non fourni'
            ], 400);
        }

        $request->validate([
            'rapport_consultations_id' => 'nullable|exists:ops_tbl_rapport_consultations,id',
            'description' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.name' => 'required|string',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.protocol' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $ordonnance = Ordonnance::create([
                'rapport_consultations_id' => $request->rapport_consultations_id,
                'description' => $request->description,
                'created_by' => $auth->id
            ]);

            foreach ($request->products as $product) {
                OrdonnanceProduit::create([
                    'ordonnance_id' => $ordonnance->id,
                    'nom' => $product['name'],
                    'quantite' => $product['quantity'],
                    'protocole' => $product['protocol'],
                    'created_by' => $auth->id
                ]);
            }

            if ($request->rapport_consultations_id) {
                \App\Models\OpsTblRapportConsultation::where('id', $request->rapport_consultations_id)
                    ->update(['can_add_ordonnance' => true]);
            }

            $rapport = optional($ordonnance->rapportConsultation);
            $client = optional($rapport->dossierConsultation->rendezVous->client);
            $consultant = optional($rapport->dossierConsultation->rendezVous->consultant);

            $centre = Centre::find($centreId);
            $media = $centre?->medias()->where('name', 'logo')->first();

            $data = [
                'ordonnance' => $ordonnance->load('produits'),
                'consultant' => $consultant->nomcomplet,
                'patient' => $client->nomcomplet_client ?? '',
                'logo' => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
                'centre' => $centre,
                'date_aujourdhui' => now()->format('d/m/Y'),
            ];

            $fileName = 'ORDONNANCE-N°' . now()->format('YmdHis') . '.pdf';
            $folderPath = 'storage/ordonnances';
            $filePath = $folderPath . '/' . $fileName;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            save_browser_shot_pdf(
                view: 'pdfs.ordonnances.ordonnance',
                data: ['data' => $data],
                folderPath: $folderPath,
                path: $filePath,
                margins: [15, 10, 15, 10],
                format: 'A5',
                direction: 'landscape'
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
