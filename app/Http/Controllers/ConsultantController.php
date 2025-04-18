<?php

namespace App\Http\Controllers;

use App\Exports\ClientsExport;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Consultant;
use App\Models\Titre;
use App\Exports\ConsultantsExport;
use App\Enums\StatusConsultEnum;
use App\Enums\TelWhatsAppEnum;
use App\Enums\TypeConsultEnum;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;
use Maatwebsite\Excel\Facades\Excel;

class ConsultantController extends Controller
{

    /**
     * @permission ConsultantController::index
     * @permission_desc Afficher la liste des consultants
     */
    public function index()
    {
        $consultants = Consultant::where('is_deleted', false) // Add the condition for is_deleted
        ->with('codeSpecialite:id,nom_specialite', 'codeTitre:id,nom_titre')
            ->paginate(10);

        return response()->json($consultants);
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

            $consultant->status_consult = $status;
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
        $consultant = Consultant::find($id);

        if (!$consultant) {
            return response()->json([
                'message' => 'Consultant Introuvable'
            ], 404);
        }

        return response()->json(['data' => $consultant], 200);
    }


    /**
     * @permission ConsultantController::search
     * @permission_desc Rechercher des consultants
     */
    public function search(Request $request)
    {
        // Récupérer les paramètres de la requête
        $query = Consultant::query();

        // Vérifier et appliquer les filtres avec LIKE
        if ($request->has('nom_consult')) {
            $query->where('nom_consult', 'like', '%' . $request->input('nom_consult') . '%');
        }

        if ($request->has('email_consul')) {
            $query->where('email_consul', 'like', '%' . $request->input('email_consul') . '%');
        }

        if ($request->has('status_consult')) {
            $query->where('status_consult', 'like', '%' . $request->input('status_consult') . '%');
        }

        if ($request->has('type_consult')) {
            $query->where('type_consult', 'like', '%' . $request->input('type_consult') . '%');
        }
        if($request->has('ref_consult')){
            $query->where('ref_consult', 'like', '%' . $request->input('ref_consult') . '%');
        }
        if ($request->has('tel1_consult')) {
            $query->where('tel1', 'like', '%' . $request->input('tel1_consult') . '%');
        }
        if($request->has('nomcomplet_consult')){
            $query->where('nomcomplet_consult', 'like', '%' . $request->input('nomcomplet_consult') . '%');
        }

        $query->where('is_deleted', false);

        // Exécuter la requête et retourner les résultats
        $consultants = $query->get();

        return response()->json($consultants);
    }

    /**
     * @permission ConsultantController::export
     * @permission_desc Exporter les données des consultants
     */
    public function export()
    {
        $filename = 'consultant-file-' . now()->format('Y-d-m') . '.xlsx';

        Excel::store(new ConsultantsExport(), $filename, 'exportconsultant');

        return response()->json([
            'url' => Storage::disk('exportconsultant')->url($filename)
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
        // Créer une requête de base
        $query = Consultant::query();

        // Appliquer les filtres de recherche
        if ($request->has('nom_consult') && $request->input('nom_consult') != '') {
            $query->where('nom_consult', 'like', '%' . $request->input('nom_consult') . '%');
        }

        if ($request->has('email_consul') && $request->input('email_consul') != '') {
            $query->where('email_consul', 'like', '%' . $request->input('email_consul') . '%');
        }

        if ($request->has('status_consult') && $request->input('status_consult') != '') {
            $query->where('status_consult', 'like', '%' . $request->input('status_consult') . '%');
        }

        if ($request->has('type_consult') && $request->input('type_consult') != '') {
            $query->where('type_consult', 'like', '%' . $request->input('type_consult') . '%');
        }

        if ($request->has('ref_consult') && $request->input('ref_consult') != '') {
            $query->where('ref_consult', 'like', '%' . $request->input('ref_consult') . '%');
        }

        if ($request->has('tel1_consult') && $request->input('tel1_consult') != '') {
            $query->where('tel1', 'like', '%' . $request->input('tel1_consult') . '%');
        }

        if ($request->has('nomcomplet_consult') && $request->input('nomcomplet_consult') != '') {
            $query->where('nomcomplet_consult', 'like', '%' . $request->input('nomcomplet_consult') . '%');
        }

        // Exécuter la requête et récupérer les consultants filtrés
        $consultants = $query->get();

        // Si aucune donnée n'est trouvée
        if ($consultants->isEmpty()) {
            return response()->json(['message' => 'Aucun consultant trouvé avec ces critères'], 404);
        }

        // Si l'option d'exportation est activée
        if ($request->has('export') && $request->input('export') == 'true') {
            $filename = 'consultants-' . now()->format('Y-d-m') . '.xlsx';

            // Exporter les consultants filtrés
            Excel::store(new ConsultantsExport($consultants), $filename, 'exportconsultant');

            // Retourner l'URL du fichier exporté
            return response()->json([
                'message' => 'Recherche et exportation réussis !',
                'url' => Storage::disk('exportconsultant')->url($filename)
            ]);
        }

        // Si l'export n'est pas demandé, retourner les consultants filtrés
        return response()->json($consultants);
    }

    /**
     * @permission ConsultantController::store
     * @permission_desc Enregistrer un consultant
     */
    public function store(Request $request)
    {
        $authUser = User::first(); // Récupère un utilisateur au hasard
        if (!$authUser) {
            return response()->json(['message' => 'Aucun utilisateur par défaut trouvé.'], 400);
        }

        // Validation des données sans 'user_id'
        $validated = $request->validate([
            'code_hopi' => 'required|exists:hopitals,id',
            'code_service_hopi' => 'required|exists:service__hopitals,id',
            'code_specialite' => 'required|exists:specialites,id',
            'code_titre' => 'required|exists:titres,id',
            'nom_consult' => 'required|string',
            'prenom_consult' => 'required|string',
            'tel_consult' => 'required|string|unique:consultants,tel_consult',
            'tel1_consult' => 'nullable|string|unique:consultants,tel1_consult',
            'email_consul' => 'required|email|unique:consultants,email_consul',
            'type_consult' => ['required', new Enum(TypeConsultEnum::class)],
            'TelWhatsApp' => 'nullable|in:Oui,Non',
        ]);

        // Vérifier si des champs requis sont vides et retourner un message d'erreur générique
        $missingFields = [];

        $fields = [
            'code_hopi', 'code_service_hopi', 'code_specialite', 'code_titre', 'nom_consult',
            'prenom_consult', 'tel_consult', 'tel1_consult', 'email_consul', 'type_consult'
        ];

        foreach ($fields as $field) {
            if (empty($request->input($field))) {
                $missingFields[] = $field;
            }
        }

        if (count($missingFields) > 0) {
            return response()->json(['message' => 'Tous les champs sont requis.'], 400);
        }

        // Récupérer le titre
        $titre = Titre::find($validated['code_titre']);
        if (!$titre) {
            return response()->json(['message' => 'Titre non trouvé'], 404);
        }

        // Générer le nom complet du consultant
        $validated['nomcomplet_consult'] = $titre->nom_titre . ' ' . $validated['nom_consult'] . ' ' . $validated['prenom_consult'];

        // Générer une référence unique
        $validated['ref_consult'] = 'C' . now()->format('ymdHis') . mt_rand(10, 99);

        // Assigner user_id et create_by_consult avec l'ID de l'utilisateur authentifié si non fournis
        $validated['user_id'] = $authUser->id; // Assignation automatique de l'utilisateur authentifié
        $validated['create_by_consult'] =  $authUser->id;

        // Créer le consultant
        $consultant = Consultant::create($validated);

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

        // Validation des données
        $validated = $request->validate([
            'code_hopi' => 'sometimes|exists:hopitals,id',
            'code_service_hopi' => 'sometimes|exists:service__hopitals,id',
            'code_specialite' => 'sometimes|exists:specialites,id',
            'code_titre' => 'sometimes|exists:titres,id',
            'nom_consult' => 'sometimes|string',
            'prenom_consult' => 'sometimes|string',
            'tel_consult' => 'sometimes|string',
            'tel1_consult' => 'sometimes|string',
            'email_consul' => 'sometimes|email|unique:consultants,email_consul,' . $id,
            'type_consult' => ['sometimes', new Enum(TypeConsultEnum::class)],
            'TelWhatsApp' => ['sometimes', new Enum(TelWhatsAppEnum::class)],
        ]);

        // Assigner automatiquement l'ID de l'utilisateur authentifié à 'user_id' si ce n'est pas dans la requête
        $authUser = User::first(); // Par exemple, on récupère un utilisateur authentifié
        $validated['user_id'] = $authUser->id; // Assignation automatique de l'utilisateur authentifié
        $validated['update_by_consult'] =  $authUser->id;

        // Vérification si un titre est présent dans la requête
        if ($request->has('code_titre') || $request->has('nom_consult') || $request->has('prenom_consult')) {
            // Récupérer le titre à partir de l'ID dans la requête ou utiliser celui du consultant existant
            $titre = Titre::find($request->code_titre ?? $consultant->code_titre);
            $titreNom = $titre ? $titre->nom_titre : '';

            // Construire le nom complet avec titre, nom et prénom
            $validated['nomcomplet_consult'] = trim("$titreNom " .
                ($request->nom_consult ?? $consultant->nom_consult) . ' ' .
                ($request->prenom_consult ?? $consultant->prenom_consult)
            );
        }

        // Mise à jour des données du consultant
        $consultant->update($validated);

        // Retourner la réponse avec le consultant mis à jour
        return response()->json($consultant, 200);
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
