<?php

namespace App\Http\Controllers;

use App\Models\CategorieAntecedent;
use App\Models\ConfigTblSousCategorieAntecedent;
use App\Models\OpsTblAntecedent;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @permission_category Gestion des catégories d'antécédants
 */
class CategorieAntecedentController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission CategorieAntecedentController::index
     * @permission_desc Afficher  la liste des catégories antécédants
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 5);
        $page = $request->input('page', 1);

        $antecedents = CategorieAntecedent::where('is_deleted', false)
            ->with(['creator:id,login', 'updater:id,login', 'sousCategorieAntecedent:id,name'])
            ->when($request->input('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhere('id', 'like', '%' . $search . '%');
                });
            })
            ->latest()
            ->paginate(perPage: $perPage, page: $page);

        return response()->json([
            'data' => $antecedents->items(),
            'current_page' => $antecedents->currentPage(),
            'last_page' => $antecedents->lastPage(),
            'total' => $antecedents->total(),
        ]);
    }




    /**
     * Display a listing of the resource.
     * @permission CategorieAntecedentController::store
     * @permission_desc Creation des catégories antécédants
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $validated = $request->validate([
            'name' => 'required|unique:config_tbl_categorie_antecedents,name',
            'souscategorie_antecedent_id' => 'required|exists:configtbl_souscategorie_antecedent,id',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'name.unique' => 'Ce nom existe déjà.',
            'souscategorie_antecedent_id.required' => 'La sous-catégorie est obligatoire.',
            'souscategorie_antecedent_id.exists' => 'Cette sous-catégorie n\'existe pas.',
        ]);

        $validated['created_by'] = $auth->id;

        $categorie = CategorieAntecedent::create($validated);
        $categorie->load(['creator:id,login', 'updater:id,login', 'sousCategorieAntecedent:id,name']);

        return response()->json([
            'data' => $categorie,
            'message' => 'Catégorie antécédent créée avec succès'
        ], 201);
    }

    /**
     * Display the specified resource.
     * @permission CategorieAntecedentController::show
     * @permission_desc Afficher la catégorie antécédent par son ID
     */
    public function show(string $id)
    {
        // Récupérer la catégorie antécédent qui n'est pas supprimée
        $categorie = CategorieAntecedent::where('id', $id)->where('is_deleted', false)->first();

        if (!$categorie) {
            return response()->json([
                'message' => 'Catégorie antécédent non trouvée.'
            ], 404);
        }
        return response()->json([
            'data' => $categorie,
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission CategorieAntecedentController::update
     * @permission_desc Modification des catégories antécédants
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        $categorie = CategorieAntecedent::where('id', $id)->where('is_deleted', false)->first();

        if (!$categorie) {
            return response()->json(['message' => 'Catégorie non trouvée'], 404);
        }

        $validated = $request->validate([
            'name' => ['required', Rule::unique('config_tbl_categorie_antecedents', 'name')->ignore($id)],
            'souscategorie_antecedent_id' => 'required|exists:configtbl_souscategorie_antecedent,id',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'name.unique' => 'Ce nom existe déjà.',
            'souscategorie_antecedent_id.required' => 'La sous-catégorie est obligatoire.',
            'souscategorie_antecedent_id.exists' => 'Cette sous-catégorie n\'existe pas.',
        ]);

        $validated['updated_by'] = $auth->id;

        $categorie->update($validated);
        $categorie->load(['creator:id,login', 'updater:id,login', 'sousCategorieAntecedent:id,name']);

        return response()->json([
            'data' => $categorie,
            'message' => 'Catégorie antécédent mise à jour avec succès'
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission CategorieAntecedentController::destroy
     * @permission_desc Suppression des catégories antécédants
     */
    public function destroy(string $id)
    {
        // Vérifier si la catégorie existe
        $categorie = CategorieAntecedent::findOrFail($id);

        // Vérifier si elle est utilisée dans ops_tbl_antecedents
        $isUsed = OpsTblAntecedent::where('categorie_antecedent_id', $id)->exists();

        if ($isUsed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de supprimer : cette catégorie est utilisée dans des antécédents.'
            ], 400);
        }

        // Soft delete via is_deleted
        $categorie->is_deleted = true;
        $categorie->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Catégorie supprimée avec succès.'
        ]);
    }
    public function UpdateStatus(Request $request, $id, $status){
        $categorie = CategorieAntecedent::find($id);
        if(!$categorie){
            return response()->json(['message'=> 'Catégorie Introuvable'], 404);
        }
        if($categorie->is_deleted){
            return response()->json(['message' => 'Impossible de mettre à jour une catégorie supprimé']);
        }
        if(!in_array($status, ['actif', 'inactif'])){
            return response()->json(['message'=> 'Le status est obligatoire'], 400);
        }
        $categorie->status = $status;
        $categorie->save();
        return response()->json([
            'data' => $categorie,
            'message' => 'Statut mis à jour avec succès'
        ],200);
    }

}
