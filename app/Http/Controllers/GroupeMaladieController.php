<?php

namespace App\Http\Controllers;

use App\Imports\GroupeMaladieImport;
use App\Models\ClasseMaladie;
use App\Models\GroupeMaladie;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;


/**
 * @permission_category Gestion des classes de maladies
 * @permission_module Paramètres Applicatifs
 */

class GroupeMaladieController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission GroupeMaladieController::store
     * @permission_desc Création des classes de maladies
     */
    public function store(Request $request)
    {
        $request->validate([
            'classe_maladie_id' => 'required|exists:disease_classes,id',
            'name'              => 'required|string|max:255|unique:disease_groups,name',
            'code'              => 'nullable|string|max:255|unique:disease_groups,code',
        ]);

        $auth = auth()->user();

        $groupe = GroupeMaladie::create([
            'classe_maladie_id' => $request->classe_maladie_id,
            'name'              => $request->name,
            'code'              => $request->code,
            'created_by'        => $auth?->id,
        ]);

        return response()->json([
            'message' => 'Groupe maladie créé avec succès.',
            'data'    => $groupe,
        ], 201);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new GroupeMaladieImport(), $request->file('file'));
            return response()->json(['message' => 'Importation réussie.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission GroupeMaladieController::update
     * @permission_desc Modification des classes de maladies
     */
    public function update(Request $request, $id)
    {
        $groupe = GroupeMaladie::findOrFail($id);

        $request->validate([
            'classe_maladie_id' => 'required|exists:disease_classes,id',
            'name'              => 'required|string|max:255|unique:disease_groups,name,' . $id,
            'code'              => 'nullable|string|max:255|unique:disease_groups,code,' . $id,
        ]);

        $auth = auth()->user();

        $groupe->update([
            'classe_maladie_id' => $request->classe_maladie_id,
            'name'              => $request->name,
            'code'              => $request->code,
            'updated_by'        => $auth?->id,
        ]);

        return response()->json([
            'message' => 'Groupe maladie mis à jour avec succès.',
            'data'    => $groupe,
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission GroupeMaladieController::show
     * @permission_desc Afficher les détails des classes de maladies
     */
    public function show($id)
    {
        $groupe = GroupeMaladie::with('classeMaladie')->findOrFail($id);

        return response()->json([
            'message' => 'Détails du groupe maladie.',
            'data' => $groupe,
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission GroupeMaladieController::index
     * @permission_desc Afficher la liste des classes de maladies
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('limit', 25);

        $query = GroupeMaladie::with([
            'creator',
            'updater',
            'classeMaladie:id,name,code'
        ]);

        if ($request->filled('classe_maladie_id')) {
            $query->where('classe_maladie_id', $request->input('classe_maladie_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('classeMaladie', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('id', 'like', "%{$search}%");
                    });
            });
        }

        $results = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => $results->items(),
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
            'total' => $results->total(),
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission GroupeMaladieController::updateStatus
     * @permission_desc Activer/Désactiver les classes de maladies
     */
    public function updateStatus(Request $request, $id)
    {
        $auth = auth()->user();
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $groupe = GroupeMaladie::where('is_deleted', false)->find($id);
        $groupe->is_active = $request->is_active;
        $groupe->updated_by = $auth->id;
        $groupe->save();

        return response()->json([
            'message' => 'Statut mis à jour avec succès.',
            'data' => $groupe
        ]);
    }


    //
}
