<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\OpsTblCertificatMedical;
use App\Models\Ordonnance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @permission_category Gestion des certificats médicaux
 */
class OpsTblCertificatMedicalController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission OpsTblCertificatMedicalController::index
     * @permission_desc Afficher la liste des certificats médicaux
     */
    public function index(){

    }

    /**
     * Display a listing of the resource.
     * @permission OpsTblCertificatMedicalController::HistoriqueCertificatMedical
     * @permission_desc Afficher l'historique des certificats médicaux d'un client
     */
    public function HistoriqueCertificatMedical(Request $request, $client_id)
    {
        try {
            $perPage = $request->input('limit', 25);
            $page = $request->input('page', 1);

            // Vérifier si le client existe
            $client = Client::find($client_id);
            if (!$client) {
                return response()->json(['message' => 'Client non trouvé'], 404);
            }

            // Récupérer les certificats médicaux liés au client via les relations
            $certificats = OpsTblCertificatMedical::whereHas('rapportConsultation.dossierConsultation.rendezVous', function ($query) use ($client_id) {
                $query->where('client_id', $client_id);
            })
                ->with([
                    'rapportConsultation.dossierConsultation.rendezVous.client',
                    'rapportConsultation.dossierConsultation.rendezVous.consultant',
                    'creator',
                    'updater',
                ])
                ->where('is_deleted', false)
                ->orderByDesc('created_at')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data' => $certificats->items(),
                'current_page' => $certificats->currentPage(),
                'last_page' => $certificats->lastPage(),
                'total' => $certificats->total(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des certificats médicaux.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission OpsTblCertificatMedicalController::store
     * @permission_desc Creer un certificat médical pour des dossiers de consultations
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $auth = auth()->user();

            $request->validate([
                'type' => 'required|in:Certificat d\'aptitude,Certificat médical',
                'commentaire' => 'nullable|string',
                'nbre_jour_repos' => 'nullable|integer|min:1|required_if:type,Certificat médical',
                'rapport_consultation_id' => 'nullable|exists:ops_tbl_rapport_consultations,id',
            ]);

            $certificat = OpsTblCertificatMedical::create([
                'type' => $request->type,
                'commentaire' => $request->commentaire,
                'nbre_jour_repos' => $request->nbre_jour_repos,
                'rapport_consultation_id' => $request->rapport_consultation_id,
                'created_by' => $auth->id,
            ]);

            $data = [];

            if ($request->type === "Certificat médical") {
                $rapport = optional($certificat->rapportConsultation);
                $client = optional($rapport->dossierConsultation->rendezVous->client);
                $consultant = optional($rapport->dossierConsultation->rendezVous->consultant);

                $data = [
                    'consultant' => $consultant,
                    'client' => $client,
                    'certificat' => $certificat,
                ];

                $fileName = 'certificat-medical-' . now()->format('YmdHis') . '.pdf';
                $folderPath = 'storage/certificats-medicals';
                $filePath = $folderPath . '/' . $fileName;

                save_browser_shot_pdf(
                    view: 'pdfs.certificats.fiche-certificat',
                    data: $data,
                    folderPath: $folderPath,
                    path: $filePath,
                    margins: [15, 10, 15, 10],
                    format: 'A4',
                    direction: 'portrait'
                );
            }

            if ($request->type === "Certificat d'aptitude") {
                $rapport = optional($certificat->rapportConsultation);
                $dossier = optional($rapport->dossierConsultation);

                // récupérer le client et sa société
                $client = optional($dossier->rendezVous->client);
                $societe = optional($client->societe);
                $prefix = optional($client->prefix);

                // récupérer le consultant
                $consultant = optional($dossier->rendezVous->consultant);

                // récupérer le motif de consultation (premier ou spécifique selon ton besoin)
                $motif = optional($dossier->motifsConsultation()->first());

                // récupérer la catégorie et le type de visite
                $categorie_visite = optional($motif->categorieVisite)->libelle ?? null;
                $type_visite = optional($motif->typeVisite)->libelle ?? null;

                // préparer les données pour le PDF ou JSON
                $data = [
                    'client' => $client,
                    'prefix' => $prefix,
                    'societe' => $societe,
                    'consultant' => $consultant,
                    'certificat' => $certificat,
                    'categorie_visite' => $categorie_visite,
                    'type_visite' => $type_visite,
                    'rapport' => $rapport,
                ];


                $fileName = 'certificat-aptitude-' . now()->format('YmdHis') . '.pdf';
                $folderPath = 'storage/certificats-aptitude';
                $filePath = $folderPath . '/' . $fileName;

                save_browser_shot_pdf(
                    view: 'pdfs.certificats.fiche-certificat-aptitude',
                    data: $data,
                    folderPath: $folderPath,
                    path: $filePath,
                    margins: [15, 10, 15, 10],
                );
            }

            DB::commit();

            if ((!isset($filePath) || !file_exists($filePath)) && $request->type !== null) {
                return response()->json(['message' => 'Le fichier PDF n\'a pas été généré.'], 500);
            }

            $pdfContent = file_exists($filePath) ? file_get_contents($filePath) : null;
            $base64 = $pdfContent ? base64_encode($pdfContent) : null;

            return response()->json([
                'message' => 'Certificat enregistré avec succès.',
                'data' => $data,
                'base64' => $base64,
                'url' => $filePath ?? null,
                'filename' => $fileName ?? null,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Display a listing of the resource.
     * @permission OpsTblCertificatMedicalController::update
     * @permission_desc Modifier un certificat médical pour des dossiers de consultations
     */

    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        $request->validate([
            'type' => 'nullable|string|max:255',
            'commentaire' => 'nullable|string',
            'nbre_jour_repos' => 'nullable|integer',
            'motif_consultation_id' => 'nullable|exists:ops_tbl__motif_consultations,id',
        ]);

        $certificat = OpsTblCertificatMedical::findOrFail($id);

        $certificat->update([
            'type' => $request->type,
            'commentaire' => $request->commentaire,
            'nbre_jour_repos' => $request->nbre_jour_repos,
            'motif_consultation_id' => $request->motif_consultation_id,
            'updated_by' => $auth->id,
        ]);

        return response()->json([
            'message' => 'Certificat médical mis à jour avec succès.',
            'data' => $certificat
        ], 200);
    }

    public function show($id)
    {
        $certificat = OpsTblCertificatMedical::with('motifConsultation', 'creator', 'updater')->findOrFail($id);

        return response()->json([
            'message' => 'Détails du certificat médical.',
            'data' => $certificat
        ], 200);
    }


    //
}
