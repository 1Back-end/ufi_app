<?php

namespace App\Http\Controllers;

use App\Exports\AssureurExport;
use App\Exports\ClientsExport;
use App\Exports\ConsultantExportSearch;
use App\Imports\ClasseMaladieImport;
use App\Imports\MedecinImport;
use App\Imports\PescripteursImport;
use App\Models\Centre;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ConsultantController extends Controller
{

    /**
     * @permission ConsultantController::index
     * @permission_desc Afficher la liste des consultants
     */
    public function index(Request $request){
        $perPage = $request->input('limit', 10);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante
        $consultanst = Consultant::where('is_deleted', false)
            ->with([
                'code_hopi',
                'code_specialite',
                'code_titre','centre'
            ])
            ->when($request->input('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where('ref', 'like', '%' . $search . '%')
                    ->orWhere('nom', 'like', '%' . $search . '%')
                    ->orWhere('prenom', 'like', '%' . $search . '%')
                    ->orWhere('nomcomplet', 'like', '%' . $search . '%')
                    ->orWhere('tel', 'like', '%' . $search . '%')
                    ->orWhere('tel1', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
            })
            ->latest()
            ->paginate(perPage: $perPage, page: $page);

        return response()->json([
            'data' => $consultanst->items(),
            'current_page' => $consultanst->currentPage(),  // Page courante
            'last_page' => $consultanst->lastPage(),  // Dernière page
            'total' => $consultanst->total(),  // Nombre total d'éléments
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
                'code_hopi:id,nom_hopi',
                'code_specialite:id,nom_specialite',
                'code_titre:id,nom_titre',
                'code_service_hopi:id,nom_service_hopi',
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
        ], [
            'code_hopi.required' => 'Le champ hôpital est obligatoire.',
            'code_hopi.exists' => 'L\'hôpital sélectionné est invalide.',

            'code_service_hopi.required' => 'Le champ service est obligatoire.',
            'code_service_hopi.exists' => 'Le service sélectionné est invalide.',

            'code_specialite.required' => 'Le champ spécialité est obligatoire.',
            'code_specialite.exists' => 'La spécialité sélectionnée est invalide.',

            'code_titre.required' => 'Le champ titre est obligatoire.',
            'code_titre.exists' => 'Le titre sélectionné est invalide.',

            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',

            'tel.required' => 'Le numéro de téléphone principal est obligatoire.',
            'tel.unique' => 'Ce numéro de téléphone est déjà utilisé.',

            'tel1.unique' => 'Le numéro secondaire est déjà utilisé.',

            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email n\'est pas valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',

            'type.required' => 'Le type de consultant est obligatoire.',
            'TelWhatsApp.in' => 'Le champ WhatsApp doit être Oui ou Non.'
        ]);
        $titre = Titre::find($data['code_titre']);
        // Générer le nom complet du consultant
        $data['nomcomplet'] = $titre->nom_titre . ' ' . $data['nom'] . ' ' . $data['prenom'];

        // Générer une référence unique
        $data['ref'] = 'C' . now()->format('ymdHis') . mt_rand(10, 99);
        $data['created_by'] = $auth->id;
        $consultant = Consultant::create($data);

        // Retourner la réponse JSON avec l'objet consultant créé
        return response()->json([
            'message' => 'Consultant créé avec succès',
            'consultant' => $consultant
        ], 201);
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
    public function update(Request $request, string $id)
    {
        $consultant = Consultant::find($id);
        if (!$consultant) {
            return response()->json(['message' => 'Consultant non trouvé'], 404);
        }
        $auth = auth()->user();

        // Validation des données
        $validated = $request->validate([
            'code_hopi' => 'sometimes|exists:hopitals,id',
            'code_service_hopi' => 'sometimes|exists:service__hopitals,id',
            'code_specialite' => 'sometimes|exists:specialites,id',
            'code_titre' => 'sometimes|exists:titres,id',
            'nom' => 'sometimes|string',
            'prenom' => 'sometimes|string',
            'tel' => 'sometimes|string',
            'tel1' => 'nullable|string',
            'email' => 'sometimes|email|unique:consultants,email,' . $id,
            'type' => ['sometimes', new Enum(TypeConsultEnum::class)],
            'TelWhatsApp' => ['sometimes', new Enum(TelWhatsAppEnum::class)],
        ], [
            'code_hopi.exists' => 'Le code de l’hôpital est invalide.',
            'code_service_hopi.exists' => 'Le code du service hospitalier est invalide.',
            'code_specialite.exists' => 'Le code de spécialité est invalide.',
            'code_titre.exists' => 'Le code du titre est invalide.',
            'nom.string' => 'Le nom doit être une chaîne de caractères.',
            'prenom.string' => 'Le prénom doit être une chaîne de caractères.',
            'tel.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'tel1.string' => 'Le numéro secondaire doit être une chaîne de caractères.',
            'email.email' => 'L’adresse email n’est pas valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée par un autre consultant.',
            'type.enum' => 'Le type de consultant sélectionné est invalide.',
            'TelWhatsApp.enum' => 'Le numéro WhatsApp sélectionné est invalide.',
        ]);
        $validated['update_by'] =  $auth->id;
        // Vérification si un titre est présent dans la requête
        if ($request->has('code_titre') || $request->has('nom') || $request->has('prenom')) {
            // Récupérer le titre à partir de l'ID dans la requête ou utiliser celui du consultant existant
            $titre = Titre::find($request->code_titre ?? $consultant->code_titre);
            $titreNom = $titre ? $titre->nom_titre : '';

            // Construire le nom complet avec titre, nom et prénom
            $validated['nomcomplet'] = trim("$titreNom " .
                ($request->nom ?? $consultant->nom) . ' ' .
                ($request->prenom ?? $consultant->prenom)
            );
        }
        // Mise à jour des données du consultant
        $consultant->update($validated);

        // Retourner la réponse avec le consultant mis à jour
        return response()->json([
            'message' => 'Consultant mise à jour avec succès',
            'consultant' => $consultant
        ], 201);
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
}
