<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hopital;
use App\Models\User;

class HopitalController extends Controller
{
    public function index(){
        $hopital = Hopital::select('id','nom_hopi')->get();
        return response()->json($hopital);
    }
    public function get_all()
    {
        // Récupérer les hôpitaux avec pagination de 10 éléments par page
        $hopis = Hopital::paginate(10);

        // Retourner les résultats paginés sous forme de réponse JSON
        return response()->json($hopis);
    }

    public function show($id){
        $hopis = Hopital::find($id);
        if($hopis){
            return response()->json($hopis);
        }
        return  response()->json(['message' => 'Hopital non found'], 404);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_hopi' => 'required|unique:hopitals',
            'Abbreviation_hopi' => 'required',
            'addresse_hopi' => 'required',
        ]);

        $authUser = User::first(); // Récupère un utilisateur au hasard
        if (!$authUser) {
            return response()->json(['message' => 'Aucun utilisateur par défaut trouvé.'], 400);
        }

        $missingFields = [];
        $fields = [
            'nom_hopi',
            'Abbreviation_hopi',
            'addresse_hopi',
        ];

        foreach ($fields as $field) {
            if (empty($request->input($field))) {
                $missingFields[] = $field;
            }
        }

        if (count($missingFields) > 0) {
            return response()->json(['message' => 'Tous les champs sont requis.'], 400);
        }

        $hopi = Hopital::create([
            'nom_hopi' => $request->nom_hopi,
            'Abbreviation_hopi' => $request->Abbreviation_hopi,
            'addresse_hopi' => $request->addresse_hopi,
            'create_by_hopi' => $authUser->id,  // Utiliser l'utilisateur par défaut
        ]);

        return response()->json([
            'message' => 'Hopital créé avec succès',
            'data' => $hopi], 201);
    }

    public function update(Request $request, $id)
    {
        // Validation des champs
        $validated = $request->validate([
            'nom_hopi' => 'required|unique:hopitals,nom_hopi,' . $id,
            'Abbreviation_hopi' => 'required',
            'addresse_hopi' => 'required',
        ]);

        // Récupère l'hôpital à mettre à jour
        $hopi = Hopital::find($id);
        if (!$hopi) {
            return response()->json(['message' => 'Hôpital non trouvé.'], 404);
        }
        $authUser = User::first(); // Récupère un utilisateur au hasard
        if (!$authUser) {
            return response()->json(['message' => 'Aucun utilisateur par défaut trouvé.'], 400);
        }

        // Vérifie si des champs sont manquants
        $missingFields = [];
        $fields = [
            'nom_hopi',
            'Abbreviation_hopi',
            'addresse_hopi',
        ];

        foreach ($fields as $field) {
            if (empty($request->input($field))) {
                $missingFields[] = $field;
            }
        }

        if (count($missingFields) > 0) {
            return response()->json([
                'message' => 'Tous les champs sont requis !'
            ], 400);
        }

        // Mise à jour de l'hôpital
        try {
            $hopi->update([
                'nom_hopi' => $request->nom_hopi,
                'Abbreviation_hopi' => $request->Abbreviation_hopi,
                'addresse_hopi' => $request->addresse_hopi,
                'update_by_hopi' => $authUser->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour de l\'hôpital',
                'error' => $e->getMessage(),
            ], 500);
        }

        // Retourne la réponse de succès
        return response()->json([
            'message' => 'Hôpital mis à jour avec succès',
            'data' => $hopi
        ], 200);
    }

    public  function destroy (string $id)
    {
        $hopi = Hopital::find($id);
        if (!$hopi) {
            return response()->json(['message' => 'Hôpital non trouvé.'], 404);
        }
        $hopi->delete();
        return response()->json(['message' => 'Hôpital supprimé'], 200);

    }



    //
}
