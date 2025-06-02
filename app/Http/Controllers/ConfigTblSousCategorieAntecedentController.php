<?php

namespace App\Http\Controllers;

use App\Models\CategorieAntecedent;
use App\Models\ConfigTblSousCategorieAntecedent;
use Illuminate\Http\Request;
use App\Models\User;

class ConfigTblSousCategorieAntecedentController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission ConfigTblSousCategorieAntecedentController::index
     * @permission_desc Afficher  la liste des sous catégories antécédants
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 5);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante

        $antecedents= ConfigTblSousCategorieAntecedent::where('is_deleted', false)
            ->with(['creator:id,login','updater:id,login'])
            ->when($request->input('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
            })
            ->latest()->paginate(perPage: $perPage, page: $page);

        return response()->json([
            'data' => $antecedents->items(),
            'current_page' => $antecedents->currentPage(),  // Page courante
            'last_page' => $antecedents->lastPage(),  // Dernière page
            'total' => $antecedents->total(),  // Nombre total d'éléments
        ]);

        //
    }


    /**
     * Display a listing of the resource.
     * @permission ConfigTblSousCategorieAntecedentController::store
     * @permission_desc Creation des sous catégories antécédants
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $messages = [
            'name.required' => 'Le nom de la sous-catégorie est obligatoire.',
            'name.unique' => 'Cette sous-catégorie existe déjà.',
        ];

        $data = $request->validate([
            'name' => 'required|unique:configtbl_souscategorie_antecedent,name',
            'description' => 'nullable',
        ], $messages);

        $data['created_by'] = $auth->id;
        $sous_categories = ConfigTblSousCategorieAntecedent::create($data);

        return response()->json([
            'data' => $sous_categories,
            'message' => 'Enregistrement effectué avec succès'
        ]);


    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblSousCategorieAntecedentController::show
     * @permission_desc Afficher les détails des sous catégories antécédants
     */
    public function show(string $id)
    {
        $sous_categories = ConfigTblSousCategorieAntecedent::where('id',$id)->where('is_deleted',false)->first();
        if(!$sous_categories){
            return response()->json(['message'=>'Data not found'],404);
        }else{
            return response()->json($sous_categories);
        }

        //
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblSousCategorieAntecedentController::update
     * @permission_desc Modifier des sous catégories antécédants
     */
    public function update(Request $request, string $id)
    {
        $auth = auth()->user();
        $sous_categories = ConfigTblSousCategorieAntecedent::where('id', $id)->where('is_deleted', false)->firstOrFail();

        $data = $request->validate([
            'name' => 'required|unique:configtbl_souscategorie_antecedent,name,' . $id,
            'description' => 'nullable',
        ]);

        $data['updated_by'] = $auth->id;
        $sous_categories->update($data);

        return response()->json([
            'data' => $sous_categories,
            'message' => 'Modification effectuée avec succès'
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission ConfigTblSousCategorieAntecedentController::destroy
     * @permission_desc Supprimer des sous catégories antécédants
     */
    public function destroy(string $id)
    {
        // Récupérer la sous-catégorie ou échouer si introuvable
        $sousCategorie = ConfigTblSousCategorieAntecedent::findOrFail($id);

        // Vérifier si cette sous-catégorie est utilisée dans une catégorie d'antécédents
        $isUsed = CategorieAntecedent::where('souscategorie_antecedent_id', $id)->exists();

        if ($isUsed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de supprimer : cette sous-catégorie est utilisée.'
            ], 400);
        }
        // Suppression logique (soft delete)
        $sousCategorie->is_deleted = true;
        $sousCategorie->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Sous-catégorie supprimée avec succès.'
        ]);
    }
}
