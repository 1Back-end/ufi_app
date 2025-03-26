<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Specialite;
use App\Models\User;
use App\Models\Consultant;
use function Pest\Laravel\json;

class SpecialiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $specialites = Specialite::select('id','nom_specialite')
            ->where('is_deleted', false)
            ->get();
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
        $specialite = Specialite::paginate(5);
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
        // Récupérer l'utilisateur authentifié
        $authUser = User::first();
        if (!$authUser) {
            return response()->json(['message' => 'Aucun utilisateur trouvé'], 404);
        }

        // Validation des données d'entrée
        $validated = $request->validate([
            'nom_specialite' => 'required|unique:specialites,nom_specialite,' . $id, // Correctif ici
        ]);

        // Trouver la spécialité par ID
        $specialite = Specialite::find($id);
        if (!$specialite) {
            return response()->json(['message' => 'Spécialité non trouvée'], 404);
        }

        // Mettre à jour les informations de la spécialité
        try {
            $specialite->update([
                'nom_specialite' => $request->nom_specialite,
                'update_by_specialite' => $authUser->id, // L'utilisateur qui effectue la mise à jour
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }

        // Retourner la réponse de succès avec les données mises à jour
        return response()->json([
            'message' => 'Spécialité mise à jour avec succès',
            'data' => $specialite
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Find the Specialite by its ID
        $specialite = Specialite::find($id);

        if (!$specialite) {
            return response()->json(['message' => 'Spécialité non trouvée'], 404);
        }
        // Check if the specialité is associated with any consultants
        $consultantCount = Consultant::where('code_specialite', $specialite->id)->count();

        if ($consultantCount > 0) {
            return response()->json(['message' => 'La spécialité ne peut pas être supprimée car elle est associée à des consultants'], 400);
        }
        // Soft delete by setting is_deleted to true
        $specialite->is_deleted = true;
        $specialite->save();

        return response()->json([
            'message' => 'Spécialité supprimée avec succès'
        ]);
    }

}
