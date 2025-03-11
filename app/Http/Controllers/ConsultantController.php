<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Consultant;
use App\Models\Titre;
use App\Exports\ConsultantsExport;
use App\Enums\StatusConsultEnum;
use App\Enums\TelWhatsAppEnum;
use App\Enums\TypeConsultEnum;
use Illuminate\Validation\Rules\Enum;
use Maatwebsite\Excel\Facades\Excel;

class ConsultantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Consultant::latest()->get());
        //
    }
    public function updateStatus(Request $request, $id, $status)
    {
        // Trouver le consultant
        $consultant = Consultant::find($id);

        // Vérifier si le consultant existe
        if (!$consultant) {
            return response()->json(['message' => 'Consultant non trouvé'], 404);
        }

        // Vérifier si le statut est valide
        if (!in_array($status, ['Actif', 'Inactif', 'Archivé'])) {
            return response()->json(['message' => 'Statut invalide'], 400);
        }

        // Mettre à jour le statut du consultant
        $consultant->status_consult = $status;
        $consultant->save();

        // Retourner une réponse de succès
        return response()->json(['message' => 'Statut mis à jour avec succès'], 200);
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
        return Excel::store(new ConsultantsExport, 'liste-consultants.xlsx');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
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
            'status_consult' => ['required', new Enum(StatusConsultEnum::class)],
            'create_by_consult' => 'nullable|exists:users,id',
            'update_by_consult' => 'nullable|exists:users,id',
            'TelWhatsApp' => ['nullable', new Enum(TelWhatsAppEnum::class)],
        ]);

        // Récupération du titre
        $titre = Titre::find($validated['code_titre']);

        if (!$titre) {
            return response()->json(['message' => 'Titre non trouvé'], 404);
        }

        // Génération du nom complet : Titre + Nom + Prénom
        $validated['nomcomplet_consult'] = $titre->nom_titre . ' ' . $validated['nom_consult'] . ' ' . $validated['prenom_consult'];

        // Génération de la référence unique
        $validated['ref_consult'] = 'C' . now()->format('ymdHis') . mt_rand(10, 99);
        $consultant = Consultant::create($validated);
        return response()->json($consultant, 201);
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $consultant = Consultant::find($id);
        if (!$consultant) {
            return response()->json(['message' => 'Consultant non trouvé'], 404);
        }
        return response()->json($consultant, 200);

        //
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
            'user_id' => 'sometimes|exists:users,id',
            'code_hopi' => 'sometimes|exists:hopitals,id',
            'code_service_hopi' => 'sometimes|exists:service__hopitals,id',
            'code_specialite' => 'sometimes|exists:specialites,id',
            'code_titre' => 'sometimes|exists:titres,id',
            'ref_consult' => 'sometimes|string|unique:consultants,ref_consult,' . $id,
            'nom_consult' => 'sometimes|string',
            'prenom_consult' => 'sometimes|string',
            'tel_consult' => 'sometimes|string|unique:consultants,tel_consult,',
            'tel1_consult' => 'sometimes|string|unique:consultants,tel1_consult',
            'email_consul' => 'sometimes|email|unique:consultants,email_consul,' . $id,
            'type_consult' => ['sometimes', new Enum(TypeConsultEnum::class)],
            'status_consult' => ['sometimes', new Enum(StatusConsultEnum::class)],
            'create_by_consult' => 'sometimes|exists:users,id',
            'update_by_consult' => 'sometimes|exists:users,id',
            'TelWhatsApp' => ['sometimes', new Enum(TelWhatsAppEnum::class)],
        ]);

        // Récupérer le titre
        if ($request->has('code_titre') || $request->has('nom_consult') || $request->has('prenom_consult')) {
            $titre = Titre::find($request->code_titre ?? $consultant->code_titre);
            $titreNom = $titre ? $titre->nom_titre : '';

            $validated['nomcomplet_consult'] = trim("$titreNom " . ($request->nom_consult ?? $consultant->nom_consult)
                                                    . ' ' .
                                                    ($request->prenom_consult ?? $consultant->prenom_consult));
        }
        $consultant->update($validated);
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
