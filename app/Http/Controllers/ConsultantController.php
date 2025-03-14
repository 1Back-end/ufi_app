<?php

namespace App\Http\Controllers;

use App\Exports\ClientsExport;
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
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Consultant::paginate(10));
    }

    public function updateStatus(Request $request, $id, $status)
        {
            $consultant = Consultant::find($id);

            if (!$consultant) {
                return response()->json(['message' => 'Consultant non trouvé'], 404);
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

        // Exécuter la requête et retourner les résultats
        $consultants = $query->get();

        return response()->json($consultants);
    }


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
    public function store(Request $request)
    {
        // Validation des données avec des messages personnalisés pour chaque champ
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
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
            'create_by_consult' => 'nullable|exists:users,id',
            'TelWhatsApp' => 'nullable|in:Oui,Non',
        ]);

        // Vérifier si des champs requis sont vides et retourner un message d'erreur générique
        $missingFields = [];

        $fields = [
            'code_hopi', 'code_service_hopi', 'code_specialite', 'code_titre', 'nom_consult',
            'prenom_consult', 'tel_consult','tel1_consult', 'email_consul', 'type_consult'
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
    public function update(Request $request, string $id)
    {
        $consultant = Consultant::find($id);
        if (!$consultant) {
            return response()->json(['message' => 'Consultant non trouvé'], 404);
        }
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $consultant = Consultant::find($id);
        if (!$consultant) {
            return response()->json(['message' => 'Consultant non trouvé'], 404);
        }

        $consultant->delete();
        return response()->json(['message' => 'Consultant supprimé'], 200);
        //
    }
}
