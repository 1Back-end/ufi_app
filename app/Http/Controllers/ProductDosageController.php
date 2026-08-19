<?php

namespace App\Http\Controllers;

use App\Models\ProductDosage;
use Illuminate\Http\Request;
/**
 * @permission_category Gestion des dosages de produits
 * @permission_module Gestion des stocks
 * @permission_module Gestion des prestations
 */
class ProductDosageController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission ProductDosageController::index
     * @permission_desc Afficher la liste des dosages de produits
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('limit', 5);
        $page    = $request->integer('page', 1);
        $query = ProductDosage::with(['creator:id,email,nom_utilisateur', 'editor:id,email,nom_utilisateur'])
            ->when($request->has('is_active'), fn($q) => $q->where('is_active', $request->is_active))
            ->when(trim($request->search), function ($q, $search) {
                $q->where('name', 'like', "%{$search}%");
            });

        $dosages = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $dosages->items(),
            'current_page' => $dosages->currentPage(),
            'last_page'    => $dosages->lastPage(),
            'total'        => $dosages->total(),
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ProductDosageController::store
     * @permission_desc Créer un dosage de produits
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $data = $request->validate([
            'name'        => 'required|string|unique:product_dosages,name',
            'forme'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        try {
            $dosage = ProductDosage::create([
                'name'        => $data['name'],
                'forme'       => $data['forme'] ?? null,
                'description' => $data['description'] ?? null,
                'is_active'   => $data['is_active'] ?? true,
                'created_by'  => $auth?->id,
                'updated_by'  => $auth?->id,
            ]);

            return response()->json([
                'message' => 'Dosage créé avec succès.',
                'data'    => $dosage,
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Erreur création dosage : ' . $e->getMessage());

            return response()->json([
                'message' => 'Une erreur est survenue lors de la création du dosage.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission ProductDosageController::show
     * @permission_desc Afficher les détails d'un dosage de produits
     */
    public function show($id)
    {
        $dosage = ProductDosage::with(['creator:id,email,nom_utilisateur', 'editor:id,email,nom_utilisateur'])
            ->findOrFail($id);

        return response()->json($dosage);
    }

    /**
     * Display a listing of the resource.
     * @permission ProductDosageController::update
     * @permission_desc Modifier un dosage de produits
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();
        $dosage = ProductDosage::findOrFail($id);

        $data = $request->validate([
            'name'        => 'required|string|unique:product_dosages,name,' . $id,
            'forme'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        try {
            $dosage->update([
                'name'        => $data['name'],
                'forme'       => $data['forme'] ?? $dosage->forme,
                'description' => $data['description'] ?? $dosage->description,
                'is_active'   => $data['is_active'] ?? $dosage->is_active,
                'updated_by'  => $auth?->id,
            ]);

            return response()->json([
                'message' => 'Dosage mis à jour avec succès.',
                'data'    => $dosage,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour dosage : ' . $e->getMessage());

            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour du dosage.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $dosage = ProductDosage::findOrFail($id);
            $dosage->delete();

            return response()->json([
                'message' => 'Dosage supprimé avec succès.',
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Erreur suppression dosage : ' . $e->getMessage());

            return response()->json([
                'message' => 'Une erreur est survenue lors de la suppression du dosage.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission ProductDosageController::updateStatus
     * @permission_desc Activer/Désactiver un dosage de produits
     */
    public function updateStatus(Request $request, $id)
    {
        $auth = auth()->user();
        $dosage = ProductDosage::findOrFail($id);

        $data = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        try {
            $dosage->update([
                'is_active'  => $data['is_active'],
                'updated_by' => $auth?->id,
            ]);

            return response()->json([
                'message' => 'Statut du dosage mis à jour avec succès.',
                'data'    => $dosage,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour du statut.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
