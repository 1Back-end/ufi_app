<?php

namespace App\Http\Controllers;

use App\Exports\AssureurExport;
use App\Exports\ClientsExport;
use App\Exports\ConsultantExportSearch;
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

        $search = $request->input('search');
        $query = Consultant::where('is_deleted', false);
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('nom', 'like', "%$search%")
                 ->orWhere('prenom', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('tel', 'like', '%' . $search . '%')
                    ->orWhere('tel', 'like', '%' . $search . '%')
                    ->orWhere('nomcomplet', 'like', '%' . $search . '%');
            });
        }
// Récupérer les assureurs avec pagination
        $consultanst = Consultant::where('is_deleted', false)
            ->with([
                'code_hopi:id,nom_hopi',
                'code_specialite:id,nom_specialite',
                'code_titre:id,nom_titre',
            ])
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

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
        // Validation des données sans 'user_id'
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
        ]);
        $titre = Titre::find($data['code_titre']);
        // Générer le nom complet du consultant
        $data['nomcomplet'] = $titre->nom_titre . ' ' . $data['nom'] . ' ' . $data['prenom'];

        // Générer une référence unique
        $data['ref'] = 'C' . now()->format('ymdHis') . mt_rand(10, 99);
        $data['created_by'] = $auth->id;

        // Créer le consultant
        $consultant = Consultant::create($data);

        // Retourner la réponse JSON avec l'objet consultant créé
        return response()->json([
            'message' => 'Consultant créé avec succès',
            'consultant' => $consultant
        ], 201);
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
            'tel1' => 'sometimes|string',
            'email' => 'sometimes|email|unique:consultants,email,' . $id,
            'type' => ['sometimes', new Enum(TypeConsultEnum::class)],
            'TelWhatsApp' => ['sometimes', new Enum(TelWhatsAppEnum::class)],
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
