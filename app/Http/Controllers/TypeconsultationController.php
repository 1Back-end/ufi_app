<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\Typeconsultation;
use Illuminate\Http\Request;

/**
 * @permission_category Gestion des types consultations
 */
class TypeconsultationController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission TypeconsultationController::listIdName
     * @permission_desc Afficher l'id et le name du type de la consultation
     */
    public function listIdName()
    {
        $data = Typeconsultation::select('id', 'name')
            ->where('is_deleted', false)
            ->get();

        return response()->json([
            'type_consultations' => $data
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission TypeconsultationController::index
     * @permission_desc Afficher la liste des types consultations
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 5);  // Nombre d'éléments par page
        $page = $request->input('page', 1);      // Page courante
        $search = $request->input('search');     // Mot-clé de recherche

        $type_consultations = Typeconsultation::where('is_deleted', false)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhere('id', $search);  // Recherche par ID exact
                });
            })
            ->paginate(perPage: $perPage, page: $page);

        return response()->json([
            'data' => $type_consultations->items(),
            'current_page' => $type_consultations->currentPage(),
            'last_page' => $type_consultations->lastPage(),
            'total' => $type_consultations->total(),
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
     * @permission TypeconsultationController::store
     * @permission_desc Créer des types consultations
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        $data = $request->validate([
            'name' => 'required|string|unique:typeconsultations,name',
            'order' => 'required|numeric|min:0|unique:typeconsultations,order',
        ]);
        $data['created_by'] = $auth->id;
        $type_consultations = Typeconsultation::create($data);
        return response()->json([
            'data' => $type_consultations,
            'message'=> 'Enregistrement effectué avec succès'
        ]);
        //
    }

    /**
     * Display a listing of the resource.
     * @permission TypeconsultationController::show
     * @permission_desc Afficher les détails des types consultations
     */
    public function show(string $id)
    {
        $type_consultations = Typeconsultation::where('id', $id)->where('is_deleted', false)->first();
        if(!$type_consultations){
            return response()->json(['message' => 'Le type de consultation n\'existe pas',404]);
        }else{
            return response()->json($type_consultations);
        }

        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Display a listing of the resource.
     * @permission TypeconsultationController::update
     * @permission_desc Modifier des types consultations
     */
    public function update(Request $request, string $id)
    {
        $auth = auth()->user();

        $type_consultations = Typeconsultation::where('id', $id)->where('is_deleted', false)->first();

        // Valider les données reçues
        $data = $request->validate([
            'name' => 'required|string|unique:typeconsultations,name,' . $id,
            'order' => 'required|numeric|min:0|unique:typeconsultations,order,' . $id,
        ]);
        $data['updated_by'] = $auth->id;

        // Mettre à jour les données
        $type_consultations->update($data);

        // Retourner la réponse avec les données mises à jour
        return response()->json([
            'data' => $type_consultations,
            'message' => 'Mise à jour effectuée avec succès'
        ]);
        //
    }

    /**
     * Display a listing of the resource.
     * @permission TypeconsultationController::destroy
     * @permission_desc Supprimer des types consultations
     */
    public function destroy(string $id)
    {
        // Cherche le type de consultation non supprimé
        $type = Typeconsultation::where('id', $id)
            ->where('is_deleted', false)
            ->first();

        if (!$type) {
            return response()->json(['message' => 'Type de consultation introuvable ou déjà supprimé'], 404);
        }

        // Vérifie s'il est utilisé dans une consultation
        $isUsed = Consultation::where('typeconsultation_id', $id)->exists();

        if ($isUsed) {
            return response()->json([
                'message' => 'Impossible de supprimer : ce type de consultation est utilisé.'
            ], 400);
        }

        try {
            // Soft delete
            $type->is_deleted = true;
            $type->save();

            return response()->json([
                'message' => 'Type de consultation supprimé avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
