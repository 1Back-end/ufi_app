<?php

namespace App\Http\Controllers;

use App\Exports\AssureurExport;
use App\Exports\ClientsExport;
use App\Exports\ConsultantExportSearch;
use App\Imports\ClasseMaladieImport;
use App\Imports\MedecinImport;
use App\Imports\PescripteursImport;
use App\Models\Centre;
use App\Models\ConsultantDisponibilite;
use App\Models\Consultation;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Consultant;
use App\Models\Titre;
use App\Exports\ConsultantsExport;
use App\Enums\StatusConsultEnum;
use App\Enums\TelWhatsAppEnum;
use App\Enums\TypeConsultEnum;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
/**
 * @permission_category Gestion des consultants
 */
class ConsultantController extends Controller
{

    /**
     * @permission ConsultantController::index
     * @permission_desc Afficher la liste des consultants
     */
    public function index(Request $request){
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $consultants = Consultant::with([
            'code_hopi',
            'code_specialite',
            'code_titre',
            'disponibilites',
            'user'
        ])
            ->where('is_deleted', false)
            ->when($request->input('search'), function ($query) use ($request) {
                $search = $request->input('search');

                $query->where(function($q) use ($search) {
                    // Champs du consultant
                    $q->where('ref', 'like', "%$search%")
                        ->orWhere('nom', 'like', "%$search%")
                        ->orWhere('prenom', 'like', "%$search%")
                        ->orWhere('nomcomplet', 'like', "%$search%")
                        ->orWhere('tel', 'like', "%$search%")
                        ->orWhere('tel1', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%")
                        ->orWhere('type', 'like', "%$search%")
                        ->orWhere('status', 'like', "%$search%")
                        ->orWhere('id', 'like', "%$search%");

                    // Recherche dans code_specialite
                    $q->orWhereHas('code_specialite', function($qq) use ($search) {
                        $qq->where('nom_specialite', 'like', "%$search%")
                            ->orWhere('id', 'like', "%$search%");
                    });

                    // Recherche dans code_titre
                    $q->orWhereHas('code_titre', function($qq) use ($search) {
                        $qq->where('nom_titre', 'like', "%$search%")
                            ->orWhere('abbreviation_titre', 'like', "%$search%")
                            ->orWhere('id', 'like', "%$search%");
                    });

                    // Recherche dans code_hopi
                    $q->orWhereHas('code_hopi', function($qq) use ($search) {
                        $qq->where('nom_hopi', 'like', "%$search%")
                            ->orWhere('id', 'like', "%$search%")
                            ->orWhere('Abbreviation_hopi', 'like', "%$search%")
                            ->orWhere('addresse_hopi', 'like', "%$search%");

                    });

                    // Recherche dans user
                    $q->orWhereHas('user', function($qq) use ($search) {
                        $qq->where('login', 'like', "%$search%")
                            ->orWhere('email', 'like', "%$search%")
                            ->orWhere('nom_utilisateur', 'like', "%$search%")
                            ->orWhere('prenom', 'like', "%$search%");
                    });
                });
            })
            ->latest()
            ->paginate(perPage: $perPage, page: $page);

        return response()->json([
            'data' => $consultants->items(),
            'current_page' => $consultants->currentPage(),
            'last_page' => $consultants->lastPage(),
            'total' => $consultants->total(),
        ]);
    }


    /**
     * @permission ConsultantController::updateStatus
     * @permission_desc Mettre à jour le statut d'un consultant
     */

    public function updateStatus(Request $request, $id, $status)
        {
            $consultant = Consultant::find($id);
            if (!$consultant) {
                return response()->json(['message' => 'Consultant non trouvé'], 404);
            }
            // Check if the consultant is deleted
            if ($consultant->is_deleted) {
                return response()->json(['message' => 'Impossible de mettre à jour un consultant supprimé'], 400);
            }

            if (!in_array($status, ['Actif', 'Inactif', 'Archivé'])) {
                return response()->json(['message' => 'Statut invalide'], 400);
            }

            $consultant->status = $status;
            $consultant->save();

            // Retourner le consultant mis à jour
            return response()->json([
                'message' => 'Statut mis à jour avec succès',
                'consultant' => $consultant
            ], 200);
        }

    /**
     * @permission ConsultantController::show
     * @permission_desc Afficher un consultant spécifique
     */
    public function show(string $id)
    {
        $consultant = Consultant::where('is_deleted', false)
            ->with([
                'code_hopi',
                'code_specialite',
                'code_titre',
                'code_service_hopi',
                'disponibilites'
            ])
            ->findOrFail($id);
        return response()->json($consultant);
    }


    /**
     * @permission ConsultantController::search
     * @permission_desc Rechercher des consultants
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'nullable|string|max:255',
        ]);

        $searchQuery = $request->input('query', '');

        $query = Consultant::where('is_deleted', false);

        if ($searchQuery) {
            $query->where(function($query) use ($searchQuery) {
                $query->where('nom', 'like', '%' . $searchQuery . '%')
                    ->orWhere('prenom', 'like', '%' . $searchQuery . '%')
                    ->orWhere('email', 'like', '%' . $searchQuery . '%')
                    ->orWhere('tel', 'like', '%' . $searchQuery . '%')
                    ->orWhere('tel', 'like', '%' . $searchQuery . '%')
                    ->orWhere('nomcomplet', 'like', '%' . $searchQuery . '%');
            });
        }

        $consultants = $query
            ->with(['code_specialite', 'code_titre', 'code_service_hopi', 'creator', 'updater']) // chargement des relations
            ->get();

        return response()->json([
            'data' => $consultants,
        ]);
    }

    /**
     * @permission ConsultantController::export
     * @permission_desc Exporter les données des consultants
     */
    public function export()
    {
        $fileName = 'consultants-' . Carbon::now()->format('Y-m-d') . '.xlsx';

        Excel::store(new ConsultantsExport(), $fileName, 'exportconsultants');

        return response()->json([
            "message" => "Exportation des données effectuée avec succès",
            "filename" => $fileName,
            "url" => Storage::disk('exportconsultants')->url($fileName)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */


    /**
     * @permission ConsultantController::searchAndExport
     * @permission_desc Filtrer et exporter les données des consultants
     */
    public function searchAndExport(Request $request)
    {
        $request->validate([
            'query' => 'nullable|string|max:255',
        ]);

        $searchQuery = $request->input('query', '');

        $query = Consultant::where('is_deleted', false);

        if ($searchQuery) {
            $query->where(function($query) use ($searchQuery) {
                $query->where('nom', 'like', '%' . $searchQuery . '%')
                    ->orWhere('prenom', 'like', '%' . $searchQuery . '%')
                    ->orWhere('email', 'like', '%' . $searchQuery . '%')
                    ->orWhere('tel', 'like', '%' . $searchQuery . '%')
                    ->orWhere('tel', 'like', '%' . $searchQuery . '%')
                    ->orWhere('nomcomplet', 'like', '%' . $searchQuery . '%');
            });
        }

        $consultants = $query
            ->with(['code_specialite', 'code_titre', 'code_service_hopi', 'creator', 'updater']) // chargement des relations
            ->get();

        if ($consultants->isEmpty()) {
            return response()->json([
                'message' => 'Aucun assureur trouvé pour cette recherche.',
                'data' => []
            ]);
        }
        $fileName = 'consultants-recherches-' . Carbon::now()->format('Y-m-d') . '.xlsx';

        Excel::store(new ConsultantExportSearch($consultants), $fileName, 'exportconsultants');

        return response()->json([
            "message" => "Exportation des données effectuée avec succès",
            "filename" => $fileName,
            "url" => Storage::disk('exportconsultants')->url($fileName)
        ]);

    }

    /**
     * @permission ConsultantController::store
     * @permission_desc Enregistrer un consultant
     */
    public function store(Request $request)
    {
        try {
            $auth = auth()->user();

            $data = $request->validate([
                'code_hopi' => 'required|exists:hopitals,id',
                'code_service_hopi' => 'required|exists:service__hopitals,id',
                'code_specialite' => 'required|exists:specialites,id',
                'code_titre' => 'required|exists:titres,id',
                'nom' => 'required|string',
                'prenom' => 'required|string',
                'tel' => 'required|string|unique:consultants,tel',
                'tel1' => 'nullable|string|unique:consultants,tel1',
                'email' => 'required|email|unique:consultants,email',
                'type' => ['required', new Enum(TypeConsultEnum::class)],
                'TelWhatsApp' => 'nullable|in:Oui,Non',
                'jours' => 'nullable|array',
            ]);


            $titre = Titre::find($data['code_titre']);
            $data['nomcomplet'] = $titre->nom_titre . ' ' . $data['nom'] . ' ' . $data['prenom'];
            $data['ref'] = 'C' . now()->format('ymdHis') . mt_rand(10, 99);
            $data['created_by'] = $auth->id;

            // ✅ Création du consultant
            $consultant = Consultant::create($data);

            if ($data['type'] === 'Interne' && !empty($request->jours)) {
                // Mapping des jours en entier
                $joursMap = [
                    'Lundi' => 1,
                    'Mardi' => 2,
                    'Mercredi' => 3,
                    'Jeudi' => 4,
                    'Vendredi' => 5,
                    'Samedi' => 6,
                    'Dimanche' => 7,
                ];

                foreach ($request->jours as $jourNom => $plages) {
                    $jourInt = $joursMap[$jourNom] ?? null;
                    if (!$jourInt) continue; // Ignore si jour inconnu

                    foreach ($plages as $plage) {
                        if (!isset($plage['heure_debut'], $plage['heure_fin'])) {
                            continue; // Ignore les plages incomplètes
                        }

                        ConsultantDisponibilite::create([
                            'consultant_id' => $consultant->id,
                            'jour' => $jourInt,
                            'heure_debut' => $plage['heure_debut'],
                            'heure_fin' => $plage['heure_fin'],
                            'created_by' => $auth->id,
                            'updated_by' => $auth->id,
                        ]);
                    }
                }
            }
            return response()->json([
                'message' => 'Consultant et disponibilités enregistrés avec succès.',
                'consultant' => $consultant->load('disponibilites'),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation des données.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de l’enregistrement.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }




    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new PescripteursImport(), $request->file('file'));

        return response()->json([
            'message' => 'Importation effectuée avec succès.'
        ], 200);
    }

    public function import_medecin(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new MedecinImport(), $request->file('file'));

        return response()->json([
            'message' => 'Importation effectuée avec succès.'
        ], 200);
    }
    /**
     * Update the specified resource in storage.
     */
    /**
     * Display a listing of the resource.
     * @permission ConsultantController::update
     * @permission_desc Modifier un consultant
     */
    public function update(Request $request, $id)
    {
        try {
            $auth = auth()->user();

            // Récupérer le consultant
            $consultant = Consultant::findOrFail($id);

            // Validation des données
            $data = $request->validate([
                'code_hopi' => 'required|exists:hopitals,id',
                'code_service_hopi' => 'required|exists:service__hopitals,id',
                'code_specialite' => 'required|exists:specialites,id',
                'code_titre' => 'required|exists:titres,id',
                'nom' => 'required|string',
                'prenom' => 'required|string',
                'tel' => 'required|string|unique:consultants,tel,' . $consultant->id,
                'tel1' => 'nullable|string|unique:consultants,tel1,' . $consultant->id,
                'email' => 'required|email|unique:consultants,email,' . $consultant->id,
                'type' => ['required', new Enum(TypeConsultEnum::class)],
                'TelWhatsApp' => 'nullable|in:Oui,Non',
                'jours' => 'nullable|array',
            ]);

            // Mettre à jour le nom complet
            $titre = Titre::find($data['code_titre']);
            $data['nomcomplet'] = $titre->nom_titre . ' ' . $data['nom'] . ' ' . $data['prenom'];
            $data['updated_by'] = $auth->id;

            // Mise à jour du consultant
            $consultant->update($data);

            // Mapping des jours en entier
            $joursMap = [
                'Lundi' => 1,
                'Mardi' => 2,
                'Mercredi' => 3,
                'Jeudi' => 4,
                'Vendredi' => 5,
                'Samedi' => 6,
                'Dimanche' => 7,
            ];

            // Mise à jour des disponibilités
            if ($data['type'] === 'Interne') {
                // Supprimer les anciennes disponibilités
                ConsultantDisponibilite::where('consultant_id', $consultant->id)->delete();

                if (!empty($request->jours)) {
                    foreach ($request->jours as $jourNom => $plages) {
                        $jourInt = $joursMap[$jourNom] ?? null;
                        if (!$jourInt) continue;

                        foreach ($plages as $plage) {
                            if (!isset($plage['heure_debut'], $plage['heure_fin'])) {
                                continue;
                            }

                            ConsultantDisponibilite::create([
                                'consultant_id' => $consultant->id,
                                'jour' => $jourInt,
                                'heure_debut' => $plage['heure_debut'],
                                'heure_fin' => $plage['heure_fin'],
                                'created_by' => $auth->id,
                                'updated_by' => $auth->id,
                            ]);
                        }
                    }
                }
            } else {
                // Si le consultant devient externe, supprimer toutes les disponibilités
                ConsultantDisponibilite::where('consultant_id', $consultant->id)->delete();
            }

            return response()->json([
                'message' => 'Le profil du consultant a été mis à jour avec succès !',
                'consultant' => $consultant->load('disponibilites'),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation des informations fournies.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur inattendue est survenue lors de la mise à jour du consultant.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }





    /**
     * Display a listing of the resource.
     * @permission ConsultantController::destroy
     * @permission_desc Supprimer un consultant
     */
    public function destroy(string $id)
    {
        $consultant = Consultant::find($id);
        if (!$consultant) {
            return response()->json(['message' => 'Consultant non trouvé'], 404);
        }

        $consultant->is_deleted = true;
        $consultant->save();
        return response()->json(['message' => 'Consultant supprimé'], 200);
        //
    }


    public function PlanningConsultant()
    {
        try {
            DB::beginTransaction();

            $consultants = Consultant::where('is_deleted', false)
                ->with([
                    'disponibilites'
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            $data = [
                'consultants' => $consultants,
            ];

            // Chemin du fichier PDF
            $fileName   = 'planning-consultants-' . now()->format('YmdHis') . '.pdf';
            $folderPath = 'storage/planning-consultants'; // chemin absolu
            $filePath   = $folderPath . '/' . $fileName;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            // Génération du PDF
            save_browser_shot_pdf(
                view: 'pdfs.planning-consultants.planning-consultants',
                data: ['consultants' => $consultants], // clair et simple
                folderPath: $folderPath,
                path: $filePath,
                margins: [10, 10, 10, 10]
            );

            DB::commit();

            if (!file_exists($filePath)) {
                return response()->json(['message' => 'Le fichier PDF n\'a pas été généré.'], 500);
            }

            $pdfContent = file_get_contents($filePath);
            $base64 = base64_encode($pdfContent);

            return response()->json([
                'message'  => 'Planning téléchargé avec succès.',
                'data'     => $data,
                'base64'   => $base64,
                'url'      => $filePath,
                'filename' => $fileName,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la génération du planning.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
