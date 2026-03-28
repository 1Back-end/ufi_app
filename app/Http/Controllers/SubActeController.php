<?php

namespace App\Http\Controllers;

use App\Models\SubActe;
use Illuminate\Http\Request;
/**
 * @permission_category Gestion des des sous-catégories d'actes
 * @permission_module Paramètres Applicatifs
 */

class SubActeController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission SubActeController::index
     * @permission_desc Afficher  la liste des sous-catégories d'actes
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = SubActe::with(['creator', 'updater','type_acte'])
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            });

        if ($search = trim($request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $subs_actes = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $subs_actes->items(),
            'current_page' => $subs_actes->currentPage(),
            'last_page'    => $subs_actes->lastPage(),
            'total'        => $subs_actes->total(),
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission SubActeController::store
     * @permission_desc Création des sous-catégories d'actes
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        $request->validate([
            'type_acte_id' => 'required|exists:type_actes,id',
            'name' => 'required|string|max:255|unique:sub_act_categories,name',
        ]);

        $subActe = SubActe::create([
            'type_acte_id' => $request->type_acte_id,
            'name' => $request->name,
            'is_active' => $request->is_active ?? true,
            'created_by' => $auth->id
        ]);

        return response()->json([
            'message' => 'Sous-catégorie créée avec succès',
            'data' => $subActe
        ]);
        //
    }

    /**
     * Display a listing of the resource.
     * @permission SubActeController::show
     * @permission_desc Afficher des sous-catégories d'actes
     */
    public function show($id)
    {
        // Récupérer la sous-catégorie avec sa catégorie parente
        $subActe = SubActe::with(
            ['type_acte_id','creator','updater']
        )->findOrFail($id);

        return response()->json([
            'message' => 'Sous-catégorie récupérée avec succès',
            'data' => $subActe
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission SubActeController::update
     * @permission_desc Modification des sous-catégories d'actes
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        // Valider les données
        $request->validate([
            'type_acte_id' => 'sometimes|exists:type_actes,id',
            'name' => 'sometimes|string|max:255|unique:sub_act_categories,name,' . $id,
            'is_active' => 'sometimes|boolean',
        ]);

        // Récupérer la sous-catégorie
        $subActe = SubActe::findOrFail($id);

        // Mettre à jour les champs
        $subActe->update([
            'type_acte_id' => $request->type_acte_id ?? $subActe->type_acte_id,
            'name' => $request->name ?? $subActe->name,
            'is_active' => $request->is_active ?? $subActe->is_active,
            'updated_by' => $auth->id,
        ]);

        return response()->json([
            'message' => 'Sous-catégorie mise à jour avec succès',
            'data' => $subActe
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission SubActeController::updateStatus
     * @permission_desc Activer / Désactiver les sous-catégories d'actes
     */
    public function updateStatus(Request $request, $id)
    {
        $auth = auth()->user();

        // Valider le statut
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        // Récupérer la sous-catégorie
        $subActe = SubActe::findOrFail($id);

        // Mettre à jour le statut
        $subActe->update([
            'is_active' => $request->is_active,
            'updated_by' => $auth->id,
        ]);

        return response()->json([
            'message' => 'Statut de la sous-catégorie mis à jour avec succès',
            'data' => $subActe
        ]);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
