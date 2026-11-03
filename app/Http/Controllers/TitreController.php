<?php

namespace App\Http\Controllers;

use App\Models\Consultant;
use App\Models\Soins;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Titre;

/**
 * @permission_category Gestion des titres de consultants
 * @permission_module Paramètres Applicatifs
 */

class TitreController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission TitreController::index
     * @permission_desc Afficher la liste des titres des consultants
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('limit', 25);
        $page    = $request->integer('page', 1);
        $query = Titre::with(['creator:id,email,nom_utilisateur', 'editor:id,email,nom_utilisateur'])

            ->when($request->has('is_active'), fn($q) => $q->where('is_active', $request->is_active))
            ->when(trim($request->search), function ($q, $search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('nom_titre', 'like', "%{$search}%")
                        ->orWhere('abbreviation_titre', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%");
                });
            });
        $tilte = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $tilte->items(),
            'current_page' => $tilte->currentPage(),
            'last_page'    => $tilte->lastPage(),
            'total'        => $tilte->total(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @permission TitreController::store
     * @permission_desc Créer le titre d'un consultant
     */

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nom_titre' => 'required|unique:consultant_titles,nom_titre',
                'abbreviation_titre' => 'required'
            ]);

            $auth = auth()->user();

            $titre = Titre::create([
                'nom_titre' => $validated['nom_titre'],
                'abbreviation_titre' => $validated['abbreviation_titre'],
                'is_active' => true,
                'create_by' => $auth?->id,
                'updated_by' => $auth?->id,
            ]);

            return response()->json([
                'message' => __('Titre créé avec succès.'),
                'data' => $titre
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => __('Erreur de validation.'),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Erreur lors de la création du titre.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @permission TitreController::update
     * @permission_desc Modifier le titre d'un consultant
     */

    public function update(Request $request, Titre $titre)
    {
        try {
            $validated = $request->validate([
                'nom_titre' => 'required|unique:consultant_titles,nom_titre,' . $titre->id,
                'abbreviation_titre' => 'required'
            ]);

            $auth = auth()->user();

            $titre->update([
                'nom_titre' => $validated['nom_titre'],
                'abbreviation_titre' => $validated['abbreviation_titre'],
                'updated_by' => $auth?->id,
            ]);

            return response()->json([
                'message' => __('Titre mis à jour avec succès.'),
                'data' => $titre
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => __('Erreur de validation.'),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Erreur lors de la mise à jour du titre.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * @permission TitreController::show
     * @permission_desc Afficher les détails du titre d'un consultant
     */
    public function show(string $id)
    {
        $titre = Titre::with(['creator', 'editor'])->find($id);

        if (!$titre) {
            return response()->json([
                'message' => __('Titre introuvable.')
            ], 404);
        }

        return response()->json([
            'message' => __('Titre récupéré avec succès.'),
            'data' => $titre
        ], 200);
    }

    /**
     * Display the specified resource.
     * @permission TitreController::updateStatus
     * @permission_desc Activer/Désactiver le titre d'un consultant
     */
    public function updateStatus(Request $request, string $id)
    {
        try {
            $titre = Titre::find($id);

            if (!$titre) {
                return response()->json([
                    'message' => __('Titre introuvable.')
                ], 404);
            }

            $validated = $request->validate([
                'is_active' => 'required|boolean',
            ]);

            $auth = auth()->user();

            $titre->update([
                'is_active' => $validated['is_active'],
                'updated_by' => $auth?->id,
            ]);

            return response()->json([
                'message' => __('Statut du titre mis à jour avec succès.'),
                'data' => $titre
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => __('Erreur de validation.'),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Erreur lors de la mise à jour du statut.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
