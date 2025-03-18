<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Specialite;
use App\Models\User;
use function Pest\Laravel\json;

class SpecialiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $specialites = Specialite::select('id','nom_specialite')->get();
        return response()->json($specialites);
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }
    public function get_all(){
        $specialite = Specialite::paginate(10);
        return response()->json($specialite);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation des données d'entrée
        $validated = $request->validate([
            'nom_specialite' => 'required|unique:specialites,nom_specialite',  // Validation du champ obligatoire et unique
        ]);

        // Récupère l'utilisateur par défaut
        $authUser = User::first();
        if (!$authUser) {
            return response()->json(['message' => 'Aucun utilisateur trouvé'], 404);
        }

        // Création du service hospitalier
        $specialite = Specialite::create([
            'nom_specialite' => $request->nom_specialite,
            'create_by_specialite' => $authUser->id
        ]);

        // Retourne la réponse de succès
        return response()->json([
            'message' => 'Spécialité créé avec succès',
            'data' => $specialite
        ], 201);
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $specialite = Specialite::find($id);
        if (!$specialite) {
            return response()->json([
                'message' => 'Spécialité introuvable',404
            ]);

        } else {
            return  response()->json($specialite);
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
        $authUser = User::first();
        if (!$authUser) {
            return response()->json(['message' => 'Aucun utilisateur trouvé'], 404);
        }

        // Validation des données d'entrée
        $validated = $request->validate([
                'nom_specialite' => 'required|unique:specialites,nom_specialite' . $id, // Validation du champ avec exception pour l'enregistrement en cours
        ]);

        // Trouver le service hospitalier par ID
        $specialite = Specialite::find($id);
        if (!$specialite) {
            return response()->json(['message' => 'Spécialité non trouvé'], 404);
        }

        // Mettre à jour les informations du service hospitalier
        try {
            $specialite->update([
                'nom_specialite' => $request->nom_specialite,
                'update_by_specialite' => $authUser->id, // Met à jour avec l'utilisateur qui effectue la modification
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la mise à jour', 'error' => $e->getMessage()], 500);
        }

        // Retourner la réponse de succès avec les données mises à jour
        return response()->json([
            'message' => 'Spécialité mis à jour avec succès',
            'data' => $specialite
        ], 200); //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $specialite = Specialite::find($id);
        if (!$specialite) {
            return response()->json(['message' => 'Spécialité non trouvé'], 404);
        }
        $specialite->delete();
        return  response()->json([
            'message' => 'Spécialité supprimée avec succès'
        ]);
        //
    }
}
