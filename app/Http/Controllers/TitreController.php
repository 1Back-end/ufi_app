<?php

namespace App\Http\Controllers;

use App\Models\Consultant;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Titre;

class TitreController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission TitreController::index
     * @permission_desc Afficher l'id et le nom du titre
     */

    public function index()
    {
        $titres = Titre::select('id','nom_titre')
            ->where('is_deleted',false)->get();
        return response()->json($titres);
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @permission TitreController::store
     * @permission_desc Enregister un titre
     */

    public function store(Request $request)
    {
        try {
            // Validation des données d'entrée
            $validated = $request->validate([
                'nom_titre' => 'required|unique:titres,nom_titre',  // Validation obligatoire et unique
                'abbreviation_titre' => 'required'
            ]);

            // Vérification des champs manquants
            $missingFields = [];
            $fields = [
                'nom_titre',
                'abbreviation_titre'
            ];

            foreach ($fields as $field) {
                if (empty($request->input($field))) {
                    $missingFields[] = $field;
                }
            }

            // Si des champs sont manquants
            if (count($missingFields) > 0) {
                return response()->json(['message' => 'Tous les champs sont requis !'], 400);
            }
            // Récupère un utilisateur par défaut
            $authUser = User::first();
            if (!$authUser) {
                return response()->json(['message' => 'Aucun utilisateur par défaut trouvé.'], 400);
            }

            // Création du titre
            $titre = Titre::create([
                'nom_titre' => $request->nom_titre,
                'abbreviation_titre' => $request->abbreviation_titre,
                'create_by' => $authUser->id
            ]);

            // Retourne la réponse de succès
            return response()->json([
                'message' => 'Titre créé avec succès.',
                'data' => $titre
            ], 201);
        } catch (\Exception $e) {
            // Capture l'erreur et retourne un message détaillé
            return response()->json([
                'message' => 'Erreur lors de la création du titre.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @permission TitreController::update
     * @permission_desc Mettre à jour un titre
     */

    public function update(Request $request, $id)
    {
        // Validation des données d'entrée
        $validated = $request->validate([
            'nom_titre' => 'required|unique:titres,nom_titre,' . $id, // Validation obligatoire et unique, en excluant l'ID courant
            'abbreviation_titre' => 'required'
        ]);

        // Vérification des champs manquants
        $missingFields = [];
        $fields = [
            'nom_titre',
            'abbreviation_titre'
        ];

        foreach ($fields as $field) {
            if (empty($request->input($field))) {
                $missingFields[] = $field;
            }
        }

        // Si des champs sont manquants
        if (count($missingFields) > 0) {
            return response()->json(['message' => 'Tous les champs sont requis !'], 400);
        }

        // Récupérer le titre à mettre à jour
        $titre = Titre::find($id);

        // Si le titre n'existe pas
        if (!$titre) {
            return response()->json(['message' => 'Titre non trouvé.'], 404);
        }

        // Récupère un utilisateur par défaut pour la mise à jour
        $authUser = User::first();
        if (!$authUser) {
            return response()->json(['message' => 'Aucun utilisateur par défaut trouvé.'], 400);
        }

        // Mise à jour du titre
        $titre->update([
            'nom_titre' => $request->nom_titre,
            'abbreviation_titre' => $request->abbreviation_titre,
            'update_by' => $authUser->id
        ]);

        // Retourne la réponse de succès
        return response()->json([
            'message' => 'Titre mis à jour avec succès.',
            'data' => $titre
        ], 200);
    }




    /**
     * Display the specified resource.
     * @permission TitreController::show
     * @permission_desc Voir les détails
     */
    public function show(string $id)
    {
        $titre = Titre::where('id',$id)->where('is_deleted',false)->first();
        if(!$titre){
            return response()->json(['message','Titre introuvable'],404);
        }
        return  response()->json($titre);
        //
    }
    /**
     * @permission TitreController::get_all
     * @permission_desc Afficher tous les titres
     */
    public function get_all(){
        $titres = Titre::where('is_deleted',false)->paginate(5);
        return response()->json($titres);
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


    /**
     * Remove the specified resource from storage.
     * @permission TitreController::destroy
     * @permission_desc Supprimer un titre
     */
    public function destroy(string $id)
    {
        $titre = Titre::find($id);
        if(!$titre){
            return response()->json(['message','Titre introuvable'],404);
        }
        $consultantCount = Consultant::where('code_titre', $titre->id)->count();
        if ($consultantCount > 0) {
            return  response()->json(['message'=>'Le titre ne peut pas être supprimée car il est associée à des consultants'],400);
        }
        $titre->is_deleted=true;
        $titre->save();
        return response()->json(['message' => 'Titre supprimé avec succès'], 200);
        //
    }
}
