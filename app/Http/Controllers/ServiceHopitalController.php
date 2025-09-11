<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service_Hopital;
use App\Models\User;
use App\Models\Consultant;


/**
 * @permission_category Gestion des services hospitaliers
 */
class ServiceHopitalController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission ServiceHopitalController::index
     * @permission_desc Afficher l'id et le nom de l'id du service hospitalié
     */
    public function index()
    {
        $service_hopital = Service_Hopital::select('id','nom_service_hopi')
            ->where('is_deleted',false)
            ->get();
        return response()->json($service_hopital);
        //
    }

    // Affiche la liste des services hospitaliers avec pagination
    /**
     * Display a listing of the resource.
     * @permission ServiceHopitalController::get_all
     * @permission_desc Afficher la liste des services hospitaliers
     */

    public function get_all(Request $request)
    {
        $perPage = $request->input('limit', 10);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante
        $services_hopitals = Service_Hopital::where('is_deleted', false)
            ->when($request->input('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where('nom_service_hopi', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
            })
            ->latest()->paginate(perPage: $perPage, page: $page);

        return response()->json([
            'data' => $services_hopitals->items(),
            'current_page' => $services_hopitals->currentPage(),  // Page courante
            'last_page' => $services_hopitals->lastPage(),  // Dernière page
            'total' => $services_hopitals->total(),  // Nombre total d'éléments
        ]);
        //
    }

    // Crée un nouveau service hospitalier
    /**
     * Display a listing of the resource.
     * @permission ServiceHopitalController::store
     * @permission_desc Enregistrer un service hospitalier
     */
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
    /**
     * Display a listing of the resource.
     * @permission ServiceHopitalController::get_all
     * @permission_desc Afficher un service hospitalier spécifique
     */
    public function show(string $id)
    {
        $service_hopital = Service_Hopital::where('id',$id)
        ->where('is_deleted',false)->first();
        if (!$service_hopital) {
            return response()->json(['message' => 'Service Hôpital Introuvable'], 404);
        }
        return response()->json($service_hopital);
    }
    /**
     * Display a listing of the resource.
     * @permission ServiceHopitalController::update
     * @permission_desc Modifier un service hospitalier
     */
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
    /**
     * Display a listing of the resource.
     * @permission ServiceHopitalController::destroy
     * @permission_desc Suppression d'un service hospitalier
     */
    public function destroy($id)
    {
        $service = Service_Hopital::find($id);

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }
        $consultantCount = Consultant::where('code_service_hopi', $service->id)->count();
        if ($consultantCount > 0) {
            return  response()->json(['message'=>'Le service hôpital ne peut pas être supprimée car il est associée à des consultants'],400);
        }
        $service->is_deleted = true;
        $service->save();

        return response()->json(['message' => 'Service deleted successfully'], 200);
    }


    /**
     * Remove the specified resource from storage.
     */

}
