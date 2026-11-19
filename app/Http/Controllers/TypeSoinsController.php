<?php

namespace App\Http\Controllers;

use App\Models\TypeSoins;
use Illuminate\Http\Request;
use Sabberworm\CSS\Rule\Rule;

/**
 * @permission_category Gestion des types de soins
 * @permission_module Paramètres Applicatifs
 */

class TypeSoinsController extends Controller
{

    /**
     * Display a listing of the resource.
     * @permission TypeSoinsController::index
     * @permission_desc Afficher la liste des types de soins
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);
        $search = $request->input('search');

        $query = TypeSoins::with(["creator", "updater"])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhere('description', 'like', "%$search%");
                });
            })
            ->orderBy('name', 'asc')
            ->paginate(perPage: $perPage, page: $page);

        return response()->json([
            'data' => $query->items(),
            'current_page' => $query->currentPage(),
            'last_page' => $query->lastPage(),
            'total' => $query->total(),
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission TypeSoinsController::store
     * @permission_desc Créer un type de soins
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:type_soins,name',
            'order' => 'required|integer|min:0|unique:type_soins,order',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['created_by'] = $request->user()?->id;
        $data['is_active'] = $data['is_active'] ?? true;

        $typeSoins = TypeSoins::create($data);

        return response()->json([
            'data' => $typeSoins,
            'message' => 'Enregistrement effectué avec succès',
        ], 201);
    }

    /**
     * Display a listing of the resource.
     * @permission TypeSoinsController::show
     * @permission_desc Afficher les détails d'un type de soins
     */
    public function show($id)
    {
        $typeSoins = TypeSoins::with(['creator', 'updater'])->findOrFail($id);

        return response()->json([
            'data' => $typeSoins,
        ], 200);
    }


    /**
     * Display a listing of the resource.
     * @permission TypeSoinsController::update
     * @permission_desc Modifier un type de soins
     */
    public function update(Request $request, $id)
    {
        $typeSoins = TypeSoins::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:type_soins,name,' . $typeSoins->id,
            'order' => 'sometimes|required|integer|min:0|unique:type_soins,order,' . $typeSoins->id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['updated_by'] = $request->user()?->id;

        $typeSoins->update($data);

        return response()->json([
            'data' => $typeSoins->fresh(),
            'message' => 'Mise à jour effectuée avec succès',
        ], 200);
    }

    /**
     * Display a listing of the resource.
     * @permission TypeSoinsController::updateStatus
     * @permission_desc Activer/Désactiver un type de soins
     */
    public function updateStatus(Request $request, $id)
    {
        $typeSoins = TypeSoins::findOrFail($id);

        $typeSoins->is_active = !$typeSoins->is_active;
        $typeSoins->save();

        return response()->json([
            'data' => $typeSoins->fresh(['creator', 'updater']),
            'message' => $typeSoins
                ? 'Statut activé avec succès'
                : 'Statut désactivé avec succès',
        ], 200);
    }

}
