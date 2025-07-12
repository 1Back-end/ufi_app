<?php

namespace App\Http\Controllers;

use App\Models\ConfigTbl_Categories_enquetes;
use App\Models\ConfigTblTypeVisite;
use Illuminate\Http\Request;

class ConfigTblTypeVisiteController extends Controller
{

    /**
     * Display a listing of the resource.
     * @permission ConfigTblTypeVisiteController::index
     * @permission_desc Afficher la liste des types de visites
     */

    public function index(Request $request){
        $perPage = $request->input('limit', 5);
        $page = $request->input('page', 1);

        $query = ConfigTblTypeVisite::where('is_deleted', false)
            ->with(['creator:id,login', 'editor:id,login']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('libelle', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhere('id', 'like', "%$search%");
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
     * @permission ConfigTblTypeVisiteController::store
     * @permission_desc Création des types de visites
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $data = $request->validate([
            'libelle' => 'required|string|unique:config_tbl_type_visite,libelle',
            'description' => 'nullable|string',
        ]);

        $data['created_by'] = $auth->id;

        $type = ConfigTblTypeVisite::create($data);

        return response()->json([
            'message' => 'Type de visite créé avec succès.',
            'data' => $type
        ], 201);
    }
    /**
     * Display a listing of the resource.
     * @permission ConfigTblTypeVisiteController::show
     * @permission_desc Afficher les details des types de visites
     */


    public function show($id)
    {
        $type = ConfigTblTypeVisite::where('is_deleted', false)->find($id);
        if(!$type){
            return response()->json(["message" => "Type de visite n'existe pas"], 404);
        }else{
            return response()->json([
                'type' => $type
            ]);
        }

    }
    /**
     * Display a listing of the resource.
     * @permission ConfigTblTypeVisiteController::update
     * @permission_desc Modification des types de visites
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();
        $type = ConfigTblTypeVisite::findOrFail($id);

        $data = $request->validate([
            'libelle' => 'required|string|unique:config_tbl_type_visite,libelle,' . $id,
            'description' => 'nullable|string',
        ]);

        $data['updated_by'] =  $auth->id;

        $type->update($data);

        return response()->json([
            'message' => 'Type de visite mis à jour.',
            'data' => $type
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblTypeVisiteController::updateStatus
     * @permission_desc Changer le statut des types de visites
     */
    public function updateStatus(Request $request, $id)
    {
        $auth = auth()->user();
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $type = ConfigTblTypeVisite::where('is_deleted', false)->find($id);
        $type->is_active = $request->is_active;
        $type->updated_by = $auth->id;
        $type->save();

        return response()->json([
            'message' => 'Statut mis à jour avec succès.',
            'data' => $type
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission ConfigTblTypeVisiteController::destroy
     * @permission_desc Suppression des types de visites
     */
    public function destroy($id)
    {
        $type = ConfigTblTypeVisite::findOrFail($id);
        $type->is_deleted = true;
        $type->save();

        return response()->json([
            'message' => 'Type de visite supprimé.'
        ]);
    }



    //
}
