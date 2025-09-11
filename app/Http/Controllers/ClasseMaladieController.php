<?php

namespace App\Http\Controllers;

use App\Imports\ClasseMaladieImport;
use App\Models\ClasseMaladie;
use App\Models\ConfigTbl_Categories_enquetes;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @permission_category Gestion des classes maladies
 */
class ClasseMaladieController extends Controller
{

    /**
     * Display a listing of the resource.
     * @permission ClasseMaladieController::index
     * @permission_desc Afficher la liste des classes maladies
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = ClasseMaladie::where('is_deleted', false)
            ->with(['creator', 'updater']);

        // Ajout de la recherche si le champ 'search' est rempli
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
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
     * @permission ClasseMaladieController::store
     * @permission_desc Création des classes maladies
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:classe_maladie,name',
            'code' => 'required|string|max:255|unique:classe_maladie,code',
        ]);

        $user = auth()->user();

        $classe = ClasseMaladie::create([
            'name'       => $request->name,
            'code'       => $request->code,
            'created_by' => $user?->id,
        ]);

        return response()->json([
            'message' => 'Classe maladie créée avec succès.',
            'data'    => $classe,
        ], 201);
    }
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new ClasseMaladieImport(), $request->file('file'));

        return response()->json([
            'message' => 'Importation effectuée avec succès.'
        ], 200);
    }

    /**
     * Display a listing of the resource.
     * @permission ClasseMaladieController::update
     * @permission_desc Modification des classes maladies
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'code' => 'required|string|max:255|unique:classe_maladie,code,'.$id,
            'name'      => 'sometimes|string|max:255|unique:classe_maladie,name,'.$id,
            'is_active' => 'sometimes|boolean',
        ]);

        $classe = ClasseMaladie::findOrFail($id);

        $classe->update([
            'code' => $request->code,
            'name'       => $request->name ?? $classe->name,
            'is_active'  => $request->has('is_active') ? $request->is_active : $classe->is_active,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Classe maladie mise à jour avec succès.',
            'data'    => $classe,
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ClasseMaladieController::updateStatus
     * @permission_desc Changer le statut  des classes maladies
     */
    public function updateStatus(Request $request, $id)
    {
        $auth = auth()->user();
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $classe = ClasseMaladie::where('is_deleted', false)->find($id);
        $classe->is_active = $request->is_active;
        $classe->updated_by = $auth->id;
        $classe->save();

        return response()->json([
            'message' => 'Statut mis à jour avec succès.',
            'data' => $classe
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ClasseMaladieController::show
     * @permission_desc Afficher les détails des classes maladies
     */
    public function show($id)
    {
        $classe = ClasseMaladie::findOrFail($id);

        return response()->json([
            'message' => 'Détails de la classe maladie.',
            'data'    => $classe,
        ]);
    }




}
