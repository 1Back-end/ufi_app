<?php

namespace App\Http\Controllers;

use App\Exports\ConsultantsExport;
use App\Exports\DossierConsultationExport;
use App\Exports\DossierConsultationExportSearch;
use App\Models\DossierConsultation;
use App\Models\RendezVous;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * @permission_category Gestion des dossiers de consultations
 */

class DossierConsultationController extends Controller
{


    /**
     * Display a listing of the resource.
     * @permission DossierConsultationController::index
     * @permission_desc Afficher la liste des dossiers de consultations
     */

    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = DossierConsultation::where('is_deleted', false)
            ->with([
                'creator:id,login',
                'updater:id,login',
                'rendezVous:id,code,dateheure_rdv,client_id,consultant_id',
                'rendezVous.client',
                'rendezVous.consultant:id,nomcomplet,ref',
                'medias'
            ]);

        // Filtrage direct
        if ($request->filled('client_id')) {
            $query->whereHas('rendezVous', fn($q) =>
            $q->where('client_id', $request->client_id)
            );
        }
        if ($request->filled('consultant_id')) {
            $query->whereHas('rendezVous', fn($q) =>
            $q->where('consultant_id', $request->consultant_id)
            );
        }

        // 🔎 Recherche globale (incluant client et consultant)
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('poids', 'like', "%$search%")
                    ->orWhere('tension_arterielle_bd', 'like', "%$search%")
                    ->orWhere('tension_arterielle_bg', 'like', "%$search%")
                    ->orWhere('code', 'like', "%$search%")
                    ->orWhere('taille', 'like', "%$search%")
                    ->orWhere('temperature', 'like', "%$search%")
                    ->orWhere('frequence_cardiaque', 'like', "%$search%")
                    ->orWhere('saturation', 'like', "%$search%")
                    ->orWhere('autres_parametres', 'like', "%$search%")

                    // Recherche dans les clients
                    ->orWhereHas('rendezVous.client', function ($q2) use ($search) {
                        $q2->where('nomcomplet_client', 'like', "%$search%")
                            ->orWhere('ref_cli', 'like', "%$search%");
                    })

                    // Recherche dans les consultants
                    ->orWhereHas('rendezVous.consultant', function ($q3) use ($search) {
                        $q3->where('nomcomplet', 'like', "%$search%")
                            ->orWhere('ref', 'like', "%$search%");
                    })

                    ->orWhereHas('rendezVous', function ($q3) use ($search) {
                        $q3->where('code', 'like', "%$search%");
                    });
            });
        }

        $dossiers = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $dossiers->items(),
            'current_page' => $dossiers->currentPage(),
            'last_page' => $dossiers->lastPage(),
            'total' => $dossiers->total(),
        ]);
    }



    /**
     * Display a listing of the resource.
     * @permission DossierConsultationController::historiqueClient
     * @permission_desc Afficher l'historique des dossiers de consultations d'un client
     */

    public function historiqueClient(Request $request, $client_id)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = DossierConsultation::where('is_deleted', false)
            ->whereHas('rendezVous', function ($q) use ($client_id) {
                $q->where('client_id', $client_id);
            })
            ->with([
                'creator:id,login',
                'updater:id,login',
                'rendezVous:id,code,dateheure_rdv,client_id,consultant_id',
                'rendezVous.client',
                'rendezVous.consultant',
                'medias'
            ])
            ->latest();

        $dossiers = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $dossiers->items(),
            'current_page' => $dossiers->currentPage(),
            'last_page' => $dossiers->lastPage(),
            'total' => $dossiers->total(),
        ]);
    }




    /**
     * Display a listing of the resource.
     * @permission DossierConsultationController::store
     * @permission_desc Créer des dossiers de consultations
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $data = $request->validate([
            'rendez_vous_id'      => 'required|exists:rendez_vouses,id',
            'poids'               => 'required|string',
            'tension_arterielle_bd' => 'nullable|string',
            'tension_arterielle_bg' => 'nullable|string',
            'taille'              => 'nullable|string',
            'saturation'          => 'required|string',
            'autres_parametres'   => 'nullable|string',
            'temperature'         => 'nullable|string',
            'frequence_cardiaque' => 'nullable|string',
            'fichier_associe'     => 'nullable|file|max:10240',
        ]);

        DB::beginTransaction();
        try {
            // Vérifier qu’aucun dossier n’existe déjà pour ce rendez‑vous
            $existing = DossierConsultation::where('rendez_vous_id', $data['rendez_vous_id'])->first();
            if ($existing) {
                return response()->json([
                    'message' => 'Un dossier est déjà ouvert pour ce rendez‑vous.',
                    'data'    => $existing->load('medias'),
                ], 409);
            }

            // Création du dossier
            $dossier = DossierConsultation::create(array_merge($data, [
                'created_by' => $auth->id,
            ]));

            // Mise à jour du rendez‑vous
            RendezVous::where('id', $data['rendez_vous_id'])
                ->update(['etat' => 'Prises pour consultation']);

            // Upload du fichier facultatif
            if ($request->hasFile('fichier_associe')) {
                $file     = $request->file('fichier_associe');
                $path     = $file->store('dossiers', 'public');

                $dossier->medias()->create([
                    'name'      => $file->getClientOriginalName(),
                    'disk'      => 'public',
                    'path'      => $path,
                    'filename'  => $file->hashName(),
                    'mimetype'  => $file->getMimeType(),
                    'extension' => $file->getClientOriginalExtension(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Dossier créé avec succès.',
                'data'    => $dossier->load('medias'),
            ], 201);

        } catch (\Throwable $e) {

            DB::rollBack();
            Log::error('Erreur création dossier : ' . $e->getMessage());

            return response()->json([
                'message' => 'Une erreur est survenue lors de la création du dossier.',
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission DossierConsultationController::update
     * @permission_desc Modification des dossiers de consultations
     */

    public function update(Request $request, $id)
    {
        try {
            $auth = auth()->user();

            $dossier = DossierConsultation::where('is_deleted', false)
                ->findOrFail($id);

            if (!$dossier) {
                return response()->json([
                    'message' => 'Dossier non trouvé.'
                ], 404);
            }

            $data = $request->validate([
                'poids' => 'required|string',
                'tension_arterielle_bd' => 'nullable|string',
                'tension_arterielle_bg' => 'nullable|string',
                'taille' => 'nullable|string',
                'saturation' => 'required|string',
                'autres_parametres' => 'nullable|string',
                'temperature' => 'nullable|string',
                'frequence_cardiaque' => 'nullable|string',
                'fichier_associe' => 'nullable|file|max:10240',
            ]);

            $data['updated_by'] = $auth->id;

            // Met à jour les champs du dossier
            $dossier->update($data);

            // Gère un nouveau fichier s'il est envoyé
            if ($request->hasFile('fichier_associe')) {
                $file = $request->file('fichier_associe');
                $filename = $file->getClientOriginalName();
                $path = $file->store('dossiers', 'public');

                $dossier->medias()->create([
                    'name' => $filename,
                    'disk' => 'public',
                    'path' => $path,
                    'filename' => $filename,
                    'mimetype' => $file->getMimeType(),
                    'extension' => $file->getClientOriginalExtension()
                ]);
            }

            return response()->json([
                'message' => 'Dossier mis à jour avec succès.',
                'data' => $dossier->load('medias')
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Erreur de validation
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Autres erreurs
            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission DossierConsultationController::show
     * @permission_desc Afficher les détaisl des dossiers de consultations
     */
    public function show(string $id)
    {
        $dossiers = DossierConsultation::where('is_deleted', false)
            ->with([
                'creator:id,login',
                'updater:id,login',
                'rendezVous:id,code,dateheure_rdv,client_id,consultant_id',
                'rendezVous.client',
                'rendezVous.consultant',
                'medias'
            ])
            ->findOrFail($id);

        if (!$dossiers) {
            return response()->json(['message' => 'Dossiers introuvable'], 404);
        } else {
            return response()->json($dossiers);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission DossierConsultationController::export
     * @permission_desc Exporter des dossiers de consultations
     */

    public function export()
    {
        $fileName = 'dossiers-consultations-' . Carbon::now()->format('Y-m-d') . '.xlsx';

        Excel::store(new DossierConsultationExport(), $fileName, 'dossiersconsultations');

        return response()->json([
            "message" => "Exportation des données effectuée avec succès",
            "filename" => $fileName,
            "url" => Storage::disk('dossiersconsultations')->url($fileName)
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission DossierConsultationController::search_and_export
     * @permission_desc Rechercher et Exporter des dossiers de consultations
     */
    public function search_and_export(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = DossierConsultation::where('is_deleted', false)
            ->with([
                'creator:id,login',
                'updater:id,login',
                'facture:id,code',
                'rendezVous:id,code,client_id',
                'rendezVous.client:id,nomcomplet_client'
            ]);

        // Filtrage par client_id
        if ($request->filled('client_id')) {
            $query->whereHas('rendezVous', function ($q) use ($request) {
                $q->where('client_id', $request->input('client_id'));
            });
        }

        // Recherche globale
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('poids', 'like', "%$search%")
                    ->orWhere('tension', 'like', "%$search%")
                    ->orWhere('code', 'like', "%$search%")
                    ->orWhere('taille', 'like', "%$search%")
                    ->orWhere('temperature', 'like', "%$search%")
                    ->orWhere('frequence_cardiaque', 'like', "%$search%")
                    ->orWhere('saturation', 'like', "%$search%")
                    ->orWhere('autres_parametres', 'like', "%$search%");
            });
        }

        // Récupération paginée
        $dossiersPaginated = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        if ($dossiersPaginated->isEmpty()) {
            return response()->json([
                'message' => 'Aucune donnée trouvée pour cette recherche.',
                'data' => []
            ]);
        }

        // Récupération de la collection complète (sans pagination) pour l'export
        $dossiersToExport = $query->get();

        $fileName = 'dossiers-consultations-recherches-' . now()->format('Y-m-d-His') . '.xlsx';
        Excel::store(new DossierConsultationExportSearch($dossiersToExport), $fileName, 'dossiersconsultations');

        return response()->json([
            "message" => "Exportation des données effectuée avec succès",
            "filename" => $fileName,
            "url" => Storage::disk('dossiersconsultations')->url($fileName),
            "data" => $dossiersPaginated->items(),
            "current_page" => $dossiersPaginated->currentPage(),
            "last_page" => $dossiersPaginated->lastPage(),
            "total" => $dossiersPaginated->total(),
        ]);
    }


    //
}
