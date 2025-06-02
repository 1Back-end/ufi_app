<?php

namespace App\Http\Controllers;

use App\Models\Consultant;
use Illuminate\Http\Request;
use App\Models\Hopital;
use App\Models\User;

class HopitalController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission HopitalController::index
     * @permission_desc Afficher l'id et le nom de l'id et le nom de l'hôpital
     */
    public function index(){
        $hopital = Hopital::select('id','nom_hopi')
            ->where('is_deleted', false)  // Filter out deleted hospitals
            ->get();
        return response()->json($hopital);
    }
    /**
     * Display a listing of the resource.
     * @permission HopitalController::get_all
     * @permission_desc Afficher tous les hôpitaux
     */
    public function get_all(Request $request)
    {
        $perPage = $request->input('limit', 10);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante
        $hopitals = Hopital::where('is_deleted', false)
            ->when($request->input('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where('nom_hopi', 'like', '%' . $search . '%')
                    ->orWhere('Abbreviation_hopi', 'like', '%' . $search . '%')
                    ->orWhere('addresse_hopi', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
            })
            ->latest()->paginate(perPage: $perPage, page: $page);

        return response()->json([
            'data' => $hopitals->items(),
            'current_page' => $hopitals->currentPage(),  // Page courante
            'last_page' => $hopitals->lastPage(),  // Dernière page
            'total' => $hopitals->total(),  // Nombre total d'éléments
        ]);
        //
    }


    /**
     * Display a listing of the resource.
     * @permission HopitalController::show
     * @permission_desc Afficher un hôpital spécifique
     */
    public function show($id){
        $hopis = Hopital::where('id', $id)
            ->where('is_deleted', false)  // Filter out deleted hospitals
            ->first();
        if($hopis){
            return response()->json($hopis);
        }
        return  response()->json(['message' => 'Hopital non found'], 404);
    }
    /**
     * Display a listing of the resource.
     * @permission HopitalController::store
     * @permission_desc Enregistrer un hôpital
     */
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
    /**
     * Display a listing of the resource.
     * @permission HopitalController::update
     * @permission_desc Modifier un hôpital
     */

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
    /**
     * Display a listing of the resource.
     * @permission HopitalController::destroy
     * @permission_desc Supprimer un hôpital
     */

    public function destroy(string $id)
    {
        $hopi = Hopital::find($id);
        if (!$hopi) {
            return response()->json(['message' => 'Hôpital non trouvé.'], 404);
        }

        // Vérification si des consultants sont associés à cet hôpital
        $consultantCount = Consultant::where('code_hopi', $hopi->id)->count();
        if ($consultantCount > 0) {
            return response()->json(['message' => 'L\'hôpital ne peut pas être supprimé car il est associé à des consultants'], 400);
        }
        // Si tout va bien, suppression logique
        $hopi->is_deleted = true;
        $hopi->save();
        return response()->json(['message' => 'Titre supprimé avec succès'], 200);
    }




    //
}
