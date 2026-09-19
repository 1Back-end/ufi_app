<?php

namespace App\Http\Controllers;

use App\Exports\PrisesEnChargeExport;
use App\Exports\RendezVousExport;
use App\Models\DossierConsultation;
use App\Models\RendezVous;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @permission_category Gestion des rendez vous
 * @permission_module Gestion des prestations
 */

class RendezVousController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission RendezVousController::index
     * @permission_desc Afficher la liste des rendez-vous
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('limit', 25);
        $page    = $request->integer('page', 1);

        $query = RendezVous::with([
            'client',
            'consultant:id,nomcomplet',
            'createdBy:id,email,nom_utilisateur',
            'updatedBy:id,email,nom_utilisateur',
            'prestation.actes.typeActe',
            'parent:id,code,dateheure_rdv',
        ])
            ->when($request->filled('etat'), fn($q) => $q->where('etat', $request->etat))
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
            ->when($request->filled('client_id'), fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->filled('prestation_id'), fn($q) => $q->where('prestation_id', $request->prestation_id))
            ->when($request->filled('type_prestation'), function($q) use ($request) {
                $q->whereHas('prestation', function($subQ) use ($request) {
                    $subQ->where('type', $request->type_prestation);
                });
            })
            ->when($request->filled('type_acte_id'), function($q) use ($request) {
                $q->whereHas('prestation.actes', function($subQ) use ($request) {
                    $subQ->where('type_acte_id', $request->type_acte_id);
                });
            })
            ->when(trim($request->search), function ($q, $search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('nombre_jour_validite', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%")
                        ->orWhereHas('client', fn($clientQ) => $clientQ->where('nomcomplet_client', 'like', "%{$search}%"))
                        ->orWhereHas('consultant', fn($consultantQ) => $consultantQ->where('nomcomplet', 'like', "%{$search}%"));
                });
            });

        $rendezVous = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $rendezVous->items(),
            'current_page' => $rendezVous->currentPage(),
            'last_page'    => $rendezVous->lastPage(),
            'total'        => $rendezVous->total(),
        ]);
    }







    /**
     * Display a listing of the resource.
     * @permission RendezVousController::PrintRapport
     * @permission_desc Imprimer les états de consultations des rendez-vous
     */
    public function PrintRapport(Request $request)
    {
        DB::beginTransaction();

        try {
            // Période
            $date_debut = $request->input('start') ? Carbon::parse($request->input('start'))->startOfDay() : null;
            $date_fin   = $request->input('end')   ? Carbon::parse($request->input('end'))->endOfDay() : null;


            // Requête principale
            // Requête principale
            $query = RendezVous::with([
                'client.sexe',
                'consultant',
                'prestation',
            ])
                ->where('is_deleted', false)
                ->when($request->filled('consultant_id'), fn($q) => $q->where('consultant_id', $request->consultant_id))
                ->when($request->filled('client_id'), fn($q) => $q->where('client_id', $request->client_id))
                ->when($request->filled('type'), fn($q) => $q->whereHas('prestation', fn($subQ) => $subQ->where('type', $request->type)))
                ->when($date_debut && $date_fin, fn($q) => $q->whereBetween('dateheure_rdv', [$date_debut, $date_fin]));

            $rendezVous = $query->orderBy('dateheure_rdv', 'desc')->get();

            // Préparer les données pour le PDF
            $data = [
                'rendezVous' => $rendezVous,
                'periode' => $date_debut && $date_fin ? [
                    'du' => $date_debut->format('Y-m-d'),
                    'au' => $date_fin->format('Y-m-d')
                ] : null,
                'filtre' => [
                    'type' => $request->input('type'),
                    'client_id' => $request->input('client_id'),
                    'consultant_id' => $request->input('consultant_id'),
                ]
            ];

            // Nom et chemin du fichier PDF
            $fileName = 'etat-consultations-' . now()->format('YmdHis') . '.pdf';
            $folderPath = 'storage/etat_consultations';
            $filePath = $folderPath . '/' . $fileName;

            // Génération du PDF
            save_browser_shot_pdf(
                view: 'pdfs.etats.rendez_vous',
                data: ['data' => $data],
                folderPath: $folderPath,
                path: $filePath,
                margins: [15, 10, 15, 10],
                format: 'A4',
                direction: 'paysage'
            );

            DB::commit();

            // Retourner le PDF en base64
            $pdfContent = file_get_contents($filePath);
            $base64 = base64_encode($pdfContent);

            return response()->json([
                "data" => $rendezVous,
                'base64' => $base64,
                'url' => $filePath,
                'filename' => $fileName,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Une erreur est survenue lors de la génération du rapport.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission RendezVousController::rapportResume
     * @permission_desc Imprimer les rapports des consultants par prestations
     */
    public function rapportResume(Request $request)
    {
        $date_debut = $request->input('start') ? Carbon::parse($request->input('start'))->startOfDay() : null;
        $date_fin   = $request->input('end')   ? Carbon::parse($request->input('end'))->endOfDay() : null;

        $rendezVous = RendezVous::with(['consultant', 'prestation'])
            ->where('is_deleted', false)
            ->when($request->filled('consultant_id'), fn($q) => $q->where('consultant_id', $request->consultant_id))
            ->when($request->filled('client_id'), fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->filled('type'), fn($q) => $q->whereHas('prestation', fn($subQ) => $subQ->where('type', $request->type)))
            ->when($date_debut && $date_fin, fn($q) => $q->whereBetween('dateheure_rdv', [$date_debut, $date_fin]))
            ->get();

        $summary = $rendezVous->groupBy('consultant_id')->map(function ($rdvs) {
            $consultantName = $rdvs->first()->consultant->nomcomplet ?? 'N/A';
            $types = [
                'Actes' => 0,
                'Consultations' => 0,
                'Soins' => 0,
                'Produits' => 0,
                'Examen de laboratoire' => 0,
                'Hospitalisation' => 0,
            ];

            foreach ($rdvs as $rdv) {
                $typeLabel = $rdv->prestation->type_label ?? null;
                if ($typeLabel && array_key_exists($typeLabel, $types)) {
                    $types[$typeLabel]++;
                }
            }

            $nbRdv = $rdvs->count();
            $nbTypeConsultation = $rdvs->pluck('prestation.type_label')->unique()->count();

            return array_merge([
                'consultant' => $consultantName,
                'nombre_rdv' => $nbRdv,
                'nombre_type_consultation' => $nbTypeConsultation
            ], $types);
        })->values();

        // --- Génération du PDF ---
        $fileName = 'rapport_resume_' . now()->format('YmdHis') . '.pdf';
        $folderPath = 'storage/rapport_resume';
        $filePath = $folderPath . '/' . $fileName;

        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0755, true);
        }

        save_browser_shot_pdf(
            view: 'pdfs.etats.rapport_resume', // Crée une vue Blade pour afficher $summary
            data: ['summary' => $summary],
            folderPath: $folderPath,
            path: $filePath,
            margins: [15, 10, 15, 10],
            format: 'A4',
            direction: 'portrait'
        );
        DB::commit();

        // Retourner le PDF en base64 et URL
        $pdfContent = file_get_contents($filePath);
        $base64 = base64_encode($pdfContent);

        return response()->json([
            'success' => true,
            'data' => $summary,
            'url' => $filePath,
            'filename' => $fileName,
            'base64' => $base64
        ]);
    }




    public function HistoriqueRendezVous(Request $request, $client_id)
    {
        $perPage = $request->input('limit', 10);
        $page = $request->input('page', 1);

        $query = RendezVous::where('is_deleted', false)
            ->where('client_id', $client_id)
            ->with([
                'consultant:id,nomcomplet',
                'createdBy:id,email',
                'updatedBy:id,email',
                'prestation:id,type',
                'parent:id,code,dateheure_rdv'
            ])
            ->orderByDesc('dateheure_rdv'); // du plus récent au plus ancien

        // Filtrage sur le(s) état(s)
        if ($request->has('etat')) {
            // On récupère les états passés en query, séparés par des virgules
            $etats = explode(',', $request->input('etat'));
            $query->whereIn('etat', $etats);
        } else {
            // Par défaut, ces états seulement
            $query->whereIn('etat', ['Actif', 'Inactif', 'No show', 'En cours de consultation']);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $rendez_vous = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $rendez_vous->items(),
            'current_page' => $rendez_vous->currentPage(),
            'last_page' => $rendez_vous->lastPage(),
            'total' => $rendez_vous->total(),
        ]);
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Display a listing of the resource.
     * @permission RendezVousController::store
     * @permission_desc Reprogrammer un rendez-vous
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        try {
            $data = $request->validate([
                'dateheure_rdv'  => 'required|date',
                'details'        => 'required|string',
                'rendez_vous_id' => 'required|exists:rendez_vouses,id',
            ]);

            $firstRendezVous = RendezVous::find($data['rendez_vous_id']);

            $newStart = Carbon::parse($data['dateheure_rdv']);
            $newEnd   = (clone $newStart)->addMinutes($firstRendezVous->duration ?? 30);

            $conflict = RendezVous::where('id', '!=', $firstRendezVous->id)
                ->where(function ($query) use ($firstRendezVous) {
                    $query->where('client_id', $firstRendezVous->client_id)
                        ->orWhere('consultant_id', $firstRendezVous->consultant_id);
                })
                ->where(function ($query) use ($newStart, $newEnd) {
                    $query->where(function ($q) use ($newStart, $newEnd) {
                        $q->where('dateheure_rdv', '<', $newEnd)
                            ->whereRaw('DATE_ADD(dateheure_rdv, INTERVAL COALESCE(duration, 30) MINUTE) > ?', [$newStart]);
                    });
                })
                ->exists();

            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce client ou consultant a déjà un rendez-vous durant cette plage horaire.'
                ], 409);
            }

            $rendezVous = $firstRendezVous->replicate();
            $rendezVous->fill([
                'dateheure_rdv' => $data['dateheure_rdv'],
                'details'       => $data['details'],
                'created_by'    => $auth->id,
                'updated_by'    => $auth->id,
                'type'          => 'Non facturé'
            ]);
            $rendezVous->save();

            return response()->json([
                'success' => true,
                'message' => 'Enregistrement effectué avec succès',
                'data'    => $rendezVous
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission RendezVousController::update
     * @permission_desc Mettre à jour des rendez-vous
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        try {
            $data = $request->validate([
                'client_id' => 'required|integer|exists:clients,id',
                'consultant_id' => 'required|exists:consultants,id',
                'dateheure_rdv' => 'required|date',
                'heure_debut' => 'required|date_format:H:i',
                'heure_fin' => 'required|date_format:H:i|after:heure_debut',
                'details' => 'required|string',
                'nombre_jour_validite' => 'required|integer',
            ]);

            $rendezVous = RendezVous::findOrFail($id);

            $rdvDate = \Carbon\Carbon::parse($data['dateheure_rdv'])->toDateString();

            $hasConflict = RendezVous::where('consultant_id', $data['consultant_id'])
                ->whereDate('dateheure_rdv', $rdvDate)
                ->where('id', '!=', $rendezVous->id)
                ->where(function ($query) use ($data) {
                    $query->where('heure_debut', '<', $data['heure_fin'])
                        ->where('heure_fin', '>', $data['heure_debut']);
                })
                ->exists();

            if ($hasConflict) {
                return response()->json([
                    'message' => 'Le consultant a déjà un rendez-vous dans cette plage horaire.',
                ], 400);
            }

            $existingClientRdv = RendezVous::where('client_id', $data['client_id'])
                ->whereDate('dateheure_rdv', $rdvDate)
                ->where('id', '!=', $rendezVous->id)
                ->exists();

            if ($existingClientRdv) {
                return response()->json([
                    'message' => 'Un autre rendez-vous est déjà prévu pour ce client à cette date.',
                ], 400);
            }

            $data['updated_by'] = $auth->id;
            $rendezVous->update($data);

            return response()->json([
                'data' => $rendezVous,
                'message' => 'Rendez-vous mis à jour avec succès'
            ]);
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
     * @permission RendezVousController::updateStatus
     * @permission_desc Mettre à jour  le statut des rendez-vous ('Actif', 'Inactif', 'Clos', 'No show')
     */
    public function updateStatus(Request $request, $id)
    {
        $etat = $request->input('etat');

        $rendez_vous = RendezVous::find($id);

        if (!$rendez_vous) {
            return response()->json(['message' => 'Rendez-vous non trouvé'], 404);
        }

        if (!in_array($etat, ['Actif', 'Inactif', 'Clos', 'No show'])) {
            return response()->json(['message' => 'Type invalide'], 400);
        }

        $hasConsultation = DossierConsultation::where('rendez_vous_id', $id)
            ->exists();

        if ($etat === 'No show' && $hasConsultation) {
            return response()->json([
                'message' => 'Impossible de marquer comme No show : ce rendez-vous a déjà un dossier de consultation.'
            ], 400);
        }

        $rendez_vous->etat = $etat;
        $rendez_vous->save();

        return response()->json([
            'message' => 'État mis à jour avec succès',
            'rendez_vous' => $rendez_vous
        ], 200);
    }

    /**
     * Display a listing of the resource.
     * @permission RendezVousController::export_in_excel
     * @permission_desc Exporter des rendez-vous au format Excel
     */
    public function export_in_excel()
    {
        try {
            $fileName = Str::upper('rendez-vous-' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx');
            Excel::store(new RendezVousExport(), $fileName, 'exportrendezvous');
            return response()->json([
                "message" => "Exportation des données effectuée avec succès",
                "filename" => $fileName,
                "url" => Storage::disk('exportrendezvous')->url($fileName)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Une erreur est survenue lors de l'exportation des données.",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission RendezVousController::show
     * @permission_desc Afficher les details des rendez-vous
     */
    public function show(string $id)
    {
        try {
            $rendez_vous = RendezVous::with([
                    'client.sexe',
                    'consultant',
                    'createdBy',
                    'updatedBy',
                    'prestation'
                ])
                ->findOrFail($id);

            return response()->json([
                'rendez_vous' => $rendez_vous
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Prise en charge introuvable'], 404);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission RendezVousController::bulkDeliverImagingResults
     * @permission_desc Remettre les résultats d'imagérie(Radio,Echographie)
     */
    public function bulkDeliverImagingResults(Request $request)
    {
        $request->validate([
            'rendez_vous_ids' => ['required', 'array'],
            'rendez_vous_ids.*' => ['integer', 'exists:rendez_vouses,id'],
            'password' => ['required', 'string'],
        ]);

        $auth = auth()->user();

        if (!Hash::check($request->password, $auth->password)) {
            return response()->json([
                'message' => 'Mot de passe incorrect. Impossible de valider la remise.'
            ], 422);
        }

        RendezVous::whereIn('id', $request->rendez_vous_ids)->update([
            'imaging_results_delivered' => true,
            'imaging_delivered_by_user_id' => $auth->id,
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Les résultats d’imagerie sélectionnés ont été marqués comme remis avec succès.',
            'count' => count($request->rendez_vous_ids)
        ], 200);
    }

    /**
     * Display a listing of the resource.
     * @permission RendezVousController::bulkDeliverNursingResults
     * @permission_desc Remettre les résultats du nursing
     */
    public function bulkDeliverNursingResults(Request $request)
    {
        $request->validate([
            'rendez_vous_ids' => ['required', 'array'],
            'rendez_vous_ids.*' => ['integer', 'exists:rendez_vouses,id'],
            'password' => ['required', 'string'],
        ]);

        $auth = auth()->user();

        if (!Hash::check($request->password, $auth->password)) {
            return response()->json([
                'message' => 'Mot de passe incorrect. Impossible de valider la remise.'
            ], 422);
        }

        RendezVous::whereIn('id', $request->rendez_vous_ids)->update([
            'nursing_results_delivered' => true,
            'nursing_delivered_by_user_id' => $auth->id,
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Les résultats de nursing sélectionnés ont été marqués comme remis avec succès.',
            'count' => count($request->rendez_vous_ids)
        ], 200);
    }


}
