<?php

namespace App\Http\Controllers;

use App\Models\Packaging;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
/**
 * @permission_category Gestion des conditionnements produits
 * @permission_module Gestion des stocks
 */
class PackagingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission PackagingController::index
     * @permission_desc Afficher la liste des conditionnements produits
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 5);
        $page = $request->input('page', 1);

        $query = Packaging::with(['creator:id,nom_utilisateur', 'updater:id,nom_utilisateur'])
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            });

        if($search = trim($request->input('search'))){
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }
        $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page'    => $data->lastPage(),
            'total'        => $data->total(),
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission PackagingController::store
     * @permission_desc Créer un conditionnement de produits
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
        ]);
        $validated['name'] = Str::upper($validated['name']);
        $validated['created_by'] = auth()->id();
        $packaging = Packaging::create($validated);

        return response()->json([
            'message' => 'Conditionnement créé avec succès.',
            'data' => $packaging
        ], 201);
    }

    /**
     * Display a listing of the resource.
     * @permission PackagingController::show
     * @permission_desc Afficher les détails d'un conditionnement produit
     */
    public function show($id)
    {
        $packaging = Packaging::with(['creator', 'updater'])->findOrFail($id);

        return response()->json([
            'message' => 'Détails du conditionnement récupérés avec succès.',
            'data' => $packaging
        ], 200);
    }

    /**
     * Display a listing of the resource.
     * @permission PackagingController::update
     * @permission_desc Modifier un conditionnement de produits
     */
    public function update(Request $request, $id)
    {
        $packaging = Packaging::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
        ]);

        $validated['name'] = Str::upper($validated['name']);
        $validated['updated_by'] = auth()->id();

        $packaging->update($validated);
        return response()->json([
            'message' => 'Conditionnement mis à jour avec succès.',
            'data' => $packaging
        ], 200);
    }

    /**
     * Display a listing of the resource.
     * @permission PackagingController::updateStatus
     * @permission_desc Activer/Désactiver un conditionnement de produits
     */
    public function updateStatus(Request $request, $id)
    {
        $packaging = Packaging::findOrFail($id);
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);
        $validated['updated_by'] = auth()->id();
        $packaging->update($validated);
        return response()->json([
            'message' => 'Statut du conditionnement mis à jour avec succès.',
            'data' => $packaging
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Packaging $packaging)
    {
        //
    }
}
