<?php

namespace App\Http\Controllers;

use App\Models\CampagneFacture;
use App\Models\Centre;
use App\Models\Proforma;
use App\Models\ResultatExamenCampagneFacture;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ResultatExamenCampagneFactureController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission ResultatExamenCampagneFactureController::index
     * @permission_desc Afficher la liste des resultats des campagnes
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);  // Par défaut 25 éléments par page
        $page = $request->input('page', 1);

        $query = ResultatExamenCampagneFacture::with([
            'consultant',
            'patient.sexe',
            'consultant',
            'factureCampagne.campagne.elements',
            'creator',
            'updater'
        ])
            ->where('centre_id', $request->header('centre'));

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('patient_id')) $query->where('patient_id', $request->patient_id);
        if ($request->filled('consultant_id')) $query->where('consultant_id', $request->consultant_id);
        if ($request->filled('facture_campagne_id')) $query->where('facture_campagne_id', $request->facture_campagne_id);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = \Illuminate\Support\Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();

            $query->whereBetween('created_at', [$start, $end]);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%$search%")
                    ->orWhere('id', 'like', "%$search%")
                    ->orWhere('status', 'like', "%$search%");
            });
        }

        // Exécution de la requête avec pagination
        $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        // Retour de la réponse JSON
        return response()->json([
            'data' => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'total' => $data->total(),
        ]);

    }

    /**
     * Display a listing of the resource.
     * @permission ResultatExamenCampagneFactureController::store
     * @permission_desc Enregistrer les resultats des campagnes
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => __("Vous devez vous connecter à un centre !")
            ], Response::HTTP_UNAUTHORIZED);
        }

        $centre = Centre::findOrFail($centreId);

        // 🔹 Validation des données
        $validated = $request->validate([
            'consultant_id' => 'required|exists:consultants,id',
            'patient_id' => 'required|exists:clients,id',
            'facture_campagne_id' => 'required|exists:campagne_factures,id',
            'prelevement_date' => 'required|date',
            'examens' => 'required|array|min:1', // Tableau d'examens avec résultat true/false
            'examens.*.id' => 'required|integer',
            'examens.*.result' => 'required|boolean',
        ]);

        // 🔹 Génération de la référence unique
        $reference = $centre->reference . now()->format('Ymd') . Str::upper(Str::random(7));

        // 🔹 Création de l'enregistrement
        $resultat = ResultatExamenCampagneFacture::create([
            'reference' => $reference,
            'centre_id' => $centre->id,
            'consultant_id' => $validated['consultant_id'],
            'patient_id' => $validated['patient_id'],
            'prelevement_date' => $validated['prelevement_date'],
            'facture_campagne_id' => $validated['facture_campagne_id'],
            'examens' => $validated['examens'],
            'created_by' => $auth->id,
            'updated_by' => $auth->id,
        ]);
        $facture_campagnes = CampagneFacture::where('id', $validated['facture_campagne_id'])->firstOrFail();
        $facture_campagnes->update([
            'status' => 'paid',
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Résultats créés avec succès',
            'resultat' => $resultat
        ], 201);
    }

    /**
     * Display a listing of the resource.
     * @permission ResultatExamenCampagneFactureController::show
     * @permission_desc Afficher les détails des resultats des campagnes
     */
    public function show(string $id)
    {
        $resultats_campagnes = ResultatExamenCampagneFacture::with([
            'consultant',
            'patient.sexe',
            'consultant',
            'factureCampagne.campagne.elements',
            'centre'
        ])->findOrFail($id);

        return response()->json([
            'resultats_campagnes' => $resultats_campagnes
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ResultatExamenCampagneFactureController::update
     * @permission_desc Modifier les resultats des campagnes
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => __("Vous devez vous connecter à un centre !")
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 🔹 Récupération du centre
        $centre = Centre::findOrFail($centreId);

        // 🔹 Récupération du résultat existant
        $resultat = ResultatExamenCampagneFacture::where('id', $id)
            ->where('centre_id', $centre->id)
            ->firstOrFail();

        // 🔹 Validation
        $validated = $request->validate([
            'consultant_id' => 'required|exists:consultants,id',
            'patient_id' => 'required|exists:clients,id',
            'prelevement_date' => 'required|date',
            'facture_campagne_id' => 'required|exists:campagne_factures,id',
            'examens' => 'required|array|min:1',
            'examens.*.id' => 'required|integer',
            'examens.*.result' => 'required|boolean',
        ]);

        // 🔹 Mise à jour
        $resultat->update([
            'consultant_id' => $validated['consultant_id'],
            'patient_id' => $validated['patient_id'],
            'prelevement_date' => $validated['prelevement_date'],
            'facture_campagne_id' => $validated['facture_campagne_id'],
            'examens' => $validated['examens'],
            'updated_by' => $auth->id,
        ]);

        return response()->json([
            'message' => 'Résultats mis à jour avec succès',
            'resultat' => $resultat
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    /**
     * Display a listing of the resource.
     * @permission ResultatExamenCampagneFactureController::print_resultat_facture_campagne
     * @permission_desc Imprimer les resultats des examens des campagnes
     */
    public function print_resultat_facture_campagne(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            // 🔹 Charger le résultat avec ses relations
            $resultat_facture_campagne = ResultatExamenCampagneFacture::with([
                'consultant',
                'patient.sexe',
                'factureCampagne',
                'factureCampagne.campagne.elements', // 🔹 charger relation element
                'centre'
            ])
                ->where('centre_id', $request->header('centre'))
                ->where('id', $id)
                ->firstOrFail();


            $centre = Centre::find($request->header('centre'));
            $media  = $centre?->medias()->where('name', 'logo')->first();
            $logo   = $media ? 'storage/' . $media->path . '/' . $media->filename : '';

            $data = [
                'resultat_facture_campagne' => $resultat_facture_campagne,
                'logo'     => $logo,
                'centre'   => $centre,
            ];

            // 🔹 Chemins du fichier PDF
            $fileName   = strtoupper('RESULTATS-FACTURE-CAMPAGNE' . now()->format('YmdHis') . '.pdf');
            $folderPath = 'storage/resultats-factures-campagnes/' . $resultat_facture_campagne->id;
            $filePath   = $folderPath . '/' . $fileName;

            if (!is_dir($folderPath) && !mkdir($folderPath, 0755, true) && !is_dir($folderPath)) {
                throw new \RuntimeException("Impossible de créer le répertoire : {$folderPath}");
            }

            $footer = 'pdfs.reports.factures.footer';

            // 🔹 Génération du PDF
            save_browser_shot_pdf(
                view: 'pdfs.resultats-factures-campagnes.resultats-factures-campagnes',
                data: $data,
                folderPath: $folderPath,
                path: $filePath,
                margins: [10, 10, 10, 10],
                format: 'A5',                 // Format du PDF
                direction: 'landscape',     // Orientation paysage
                footer: $footer
            );
            $resultat_facture_campagne->status = 'printed';
            $resultat_facture_campagne->save();

            DB::commit();

            if (!file_exists($filePath)) {
                return response()->json(['message' => "Le fichier PDF n'a pas été généré."], 500);
            }

            $pdfContent = file_get_contents($filePath);
            $base64     = base64_encode($pdfContent);

            return response()->json([
                'resultat_facture_campagne' => $resultat_facture_campagne,
                'base64'   => $base64,
                'url'      => $filePath,
                'filename' => $fileName,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la génération du PDF.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission ResultatExamenCampagneFactureController::cancel_print_resultat_facture_campagne
     * @permission_desc Annuler les resultats des examens des campagnes
     */
    public function cancel_print_resultat_facture_campagne(Request $request, $id)
    {

    }

}
