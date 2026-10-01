<?php

namespace App\Http\Controllers;

use App\Imports\ClasseMaladieImport;
use App\Models\ClasseMaladie;
use App\Models\ConfigTbl_Categories_enquetes;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @permission_category Gestion des classes maladies
 * @permission_module Paramètres Applicatifs
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
        $perPage = (int) $request->input('limit', 5);

        $results = ClasseMaladie::with(['creator', 'updater'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);

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
            'name' => 'required|string|max:255|unique:disease_classes,name',
            'code' => 'nullable|string|max:255|unique:disease_classes,code',
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
        $classe = ClasseMaladie::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:disease_classes,name,' . $id,
            'code' => 'nullable|string|max:255|unique:disease_classes,code,' . $id,
        ]);

        $user = auth()->user();

        $classe->update([
            'name'       => $request->name,
            'code'       => $request->code,
            'updated_by' => $user?->id,
        ]);

        return response()->json([
            'message' => 'Classe maladie mise à jour avec succès.',
            'data'    => $classe,
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ClasseMaladieController::updateStatus
     * @permission_desc Activer/Désactiver les classes maladies
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
