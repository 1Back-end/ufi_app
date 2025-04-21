<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\Typeconsultation;
use Illuminate\Http\Request;

class TypeconsultationController extends Controller
{
    /**
     * Display a listing of the resource.
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
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 5);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante

        // Récupérer les assureurs avec pagination
        $type_consultations = Typeconsultation::where('is_deleted', false)
            ->paginate($perPage);

        return response()->json([
            'data' => $type_consultations->items(),
            'current_page' => $type_consultations->currentPage(),  // Page courante
            'last_page' => $type_consultations->lastPage(),  // Dernière page
            'total' => $type_consultations->total(),  // Nombre total d'éléments
        ]);
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        $data = $request->validate([
            'name' => 'required|string|unique:typeconsultations,name',
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
     * Display the specified resource.
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $auth = auth()->user();

        $type_consultations = Typeconsultation::where('id', $id)->where('is_deleted', false)->first();

        // Valider les données reçues
        $data = $request->validate([
            'name' => 'required|string|unique:typeconsultations,name,' . $id,  // Autoriser la modification du nom, mais éviter la duplication
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
     * Remove the specified resource from storage.
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
