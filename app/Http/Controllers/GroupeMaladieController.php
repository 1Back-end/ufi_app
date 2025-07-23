<?php

namespace App\Http\Controllers;

use App\Models\ClasseMaladie;
use App\Models\GroupeMaladie;
use Illuminate\Http\Request;

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
            'classe_maladie_id' => 'required|exists:classe_maladie,id',
            'name' => 'required|string|max:255|unique:groupes_maladies,name',
            'code' => 'required|string|max:255|unique:groupes_maladies,code',
        ]);

        $auth = auth()->user();

        $groupe = GroupeMaladie::create([
            'classe_maladie_id' => $request->classe_maladie_id,
            'name' => $request->name,
            'code' => $request->code,
            'created_by' => $auth->id,
        ]);

        return response()->json([
            'message' => 'Groupe maladie créé avec succès.',
            'data' => $groupe,
        ], 201);
    }

    /**
     * Display a listing of the resource.
     * @permission GroupeMaladieController::update
     * @permission_desc Modification des classes de maladies
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'classe_maladie_id' => 'sometimes|exists:classe_maladie,id',
            'name' => 'sometimes|string|max:255|unique:groupes_maladies,name,' . $id,
            'code' => 'required|string|max:255|unique:groupes_maladies,code,' . $id,
        ]);

        $groupe = GroupeMaladie::findOrFail($id);

        $groupe->update([
            'classe_maladie_id' => $request->classe_maladie_id ?? $groupe->classe_maladie_id,
            'name' => $request->name ?? $groupe->name,
            'code' => $request->code ?? $groupe->code,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Groupe maladie mis à jour avec succès.',
            'data' => $groupe,
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
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = GroupeMaladie::where('is_deleted', false)
            ->with(['creator', 'updater', 'classeMaladie']);

        if ($request->filled('classe_maladie_id')) {
            $query->where('classe_maladie_id', $request->input('classe_maladie_id'));
        }

        // Recherche globale (id, name, ou categorie.name)
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
     * @permission GroupeMaladieController::updateStatus
     * @permission_desc Changer le statut des classes de maladies
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
