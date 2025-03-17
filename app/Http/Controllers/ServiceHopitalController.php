<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service_Hopital;
use App\Models\User;
class ServiceHopitalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $service_hopital = Service_Hopital::select('id','nom_service_hopi')->get();
        return response()->json($service_hopital);
        //
    }

    // Affiche la liste des services hospitaliers avec pagination
    public function get_all()
    {
        $services_hopitals = Service_Hopital::paginate(10);
        return response()->json($services_hopitals);
    }

    // Crée un nouveau service hospitalier
    public function store(Request $request)
    {
        // Validation des données d'entrée
        $validated = $request->validate([
            'nom_service_hopi' => 'required|unique:service__hopitals,nom_service_hopi',  // Validation du champ obligatoire et unique
        ]);

        // Récupère l'utilisateur par défaut
        $authUser = User::first();
        if (!$authUser) {
            return response()->json(['message' => 'Aucun utilisateur trouvé'], 404);
        }

        // Création du service hospitalier
        $service_hopital = Service_Hopital::create([
            'nom_service_hopi' => $request->nom_service_hopi,
            'create_by_service_hopi' => $authUser->id
        ]);

        // Retourne la réponse de succès
        return response()->json([
            'message' => 'Service hospitalier créé avec succès',
            'data' => $service_hopital
        ], 201);
    }


    // Affiche un service hospitalier spécifique
    public function show(string $id)
    {
        $service_hopital = Service_Hopital::find($id);
        if (!$service_hopital) {
            return response()->json(['message' => 'Service Hôpital Introuvable'], 404);
        }
        return response()->json($service_hopital);
    }

    public function update(Request $request, string $id)
    {
        // Vérifier si l'utilisateur est authentifié
        $authUser = User::first();
        if (!$authUser) {
            return response()->json(['message' => 'Aucun utilisateur trouvé'], 404);
        }

        // Validation des données d'entrée
        $validated = $request->validate([
            'nom_service_hopi' => 'required|unique:service__hopitals,nom_service_hopi,' . $id, // Validation du champ avec exception pour l'enregistrement en cours
        ]);

        // Trouver le service hospitalier par ID
        $service_hopital = Service_Hopital::find($id);
        if (!$service_hopital) {
            return response()->json(['message' => 'Service hospitalier non trouvé'], 404);
        }

        // Mettre à jour les informations du service hospitalier
        try {
            $service_hopital->update([
                'nom_service_hopi' => $request->nom_service_hopi,
                'update_by_service_hopi' => $authUser->id, // Met à jour avec l'utilisateur qui effectue la modification
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la mise à jour', 'error' => $e->getMessage()], 500);
        }

        // Retourner la réponse de succès avec les données mises à jour
        return response()->json([
            'message' => 'Service hospitalier mis à jour avec succès',
            'data' => $service_hopital
        ], 200);
    }

    // Suppression d'un service hospitalier
    public function destroy(string $id)
    {
        // Recherche du service hospitalier à supprimer
        $service_hopital = Service_Hopital::find($id);
        if (!$service_hopital) {
            return response()->json(['message' => 'Service Hôpital Introuvable'], 404);
        }

        // Suppression du service hospitalier
        $service_hopital->delete();

        // Retourne la réponse de succès
        return response()->json(['message' => 'Service hospitalier supprimé avec succès'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */

}
