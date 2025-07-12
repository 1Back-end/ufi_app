<?php

namespace App\Http\Controllers;

use App\Models\ConfigTblCategorieVisite;
use Illuminate\Http\Request;

class ConfigTblCategorieVisiteController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategorieVisiteController::index
     * @permission_desc Afficher la liste des catégories de visites
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 5);
        $page = $request->input('page', 1);

        $query = ConfigTblCategorieVisite::where('is_deleted', false)
            ->with([
                'creator:id,login',
                'updater:id,login',
                'typeVisite:id,libelle'
            ]);

        // Filtrage par type_visite_id
        if ($request->filled('type_visite_id')) {
            $query->where('type_visite_id', $request->input('type_visite_id'));
        }

        // Recherche par mot-clé
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('libelle', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhereHas('typeVisite', function ($q2) use ($search) {
                        $q2->where('libelle', 'like', "%$search%")
                            ->orWhere('description', 'like', "%$search%");
                    });
            });
        }

        $results = $query->latest()->paginate(perPage: $perPage, page: $page);

        return response()->json([
            'data' => $results->items(),
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
            'total' => $results->total(),
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategorieVisiteController::store
     * @permission_desc Création des catégories de visites
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        $request->validate([
            'libelle' => 'required|string|unique:config_tbl_categorie_visites,libelle',
            'type_visite_id' => 'required|exists:config_tbl_type_visite,id',
            'description' => 'nullable|string',
            'sous_type' => 'required|boolean', // <- ici
        ]);

        $data = $request->all();
        $data['created_by'] = $auth->id;

        $categorie = ConfigTblCategorieVisite::create($data);
        $categorie->load(['creator:id,login', 'updater:id,login','typeVisite:id,libelle']);


        return response()->json([
            'message' => 'Catégorie créée avec succès.',
            'data' => $categorie
        ], 201);
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategorieVisiteController::show
     * @permission_desc Afficher les details des catégories de visites
     */
    public function show($id)
    {
        $categorie = ConfigTblCategorieVisite::where('is_deleted', false)->findOrFail($id);

        return response()->json(['data' => $categorie]);
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategorieVisiteController::update
     * @permission_desc Mise à jour des catégories de visites
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();
        $categorie = ConfigTblCategorieVisite::where('is_deleted', false)->findOrFail($id);

        $request->validate([
            'libelle' => 'required|string|unique:config_tbl_categorie_visites,libelle,' . $id,
            'type_visite_id' => 'nullable|exists:config_tbl_type_visite,id',
            'description' => 'nullable|string',
            'sous_type' => 'required|boolean',
        ]);

        $categorie->update(array_merge(
            $request->all(),
            ['updated_by' => $auth->id]
        ));
        $categorie->load(['creator:id,login', 'updater:id,login','typeVisite:id,libelle']);

        return response()->json([
            'message' => 'Catégorie mise à jour.',
            'data' => $categorie
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategorieVisiteController::destroy
     * @permission_desc Suppression des catégories de visites
     */
    public function destroy($id)
    {
        $auth = auth()->user();
        $categorie = ConfigTblCategorieVisite::where('is_deleted', false)->findOrFail($id);
        $categorie->is_deleted = true;
        $categorie->updated_by = $auth->id;
        $categorie->save();

        return response()->json(['message' => 'Catégorie supprimée.']);
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategorieVisiteController::updateStatus
     * @permission_desc Changer le statut des catégories de visites
     */
    public function updateStatus(Request $request, $id)
    {
        $auth = auth()->user();
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $categorie = ConfigTblCategorieVisite::where('is_deleted', false)->findOrFail($id);
        $categorie->is_active = $request->is_active;
        $categorie->updated_by = $auth->id;
        $categorie->save();

        return response()->json([
            'message' => 'Statut mis à jour.',
            'data' => $categorie
        ]);
    }
    public function getTypesParents($id)
    {
        // 1. Récupérer la catégorie demandée avec son typeVisite
        $categorie = ConfigTblCategorieVisite::with(['typeVisite:id,libelle'])->findOrFail($id);

        // 2. Vérifier si c'est un sous-type
        if (!$categorie->sous_type) {
            return response()->json([
                'message' => 'Cette catégorie n\'est pas un sous-type.'
            ], 200);
        }

        // 3. Retourner uniquement le type de visite
        return response()->json([
            'message' => 'Type de visite récupéré avec succès.',
            'type_visite' => $categorie->typeVisite
        ], 200);
    }


}
