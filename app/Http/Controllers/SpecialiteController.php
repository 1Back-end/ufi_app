<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Specialite;
use App\Models\User;
use App\Models\Consultant;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use function Pest\Laravel\json;

/**
 * @permission_category Gestion des spécialités des consultants
 * @permission_module Paramètres Applicatifs
 */

class SpecialiteController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission SpecialiteController::index
     * @permission_desc Afficher la liste des spécialités des consultants
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('limit', 25);
        $page    = $request->integer('page', 1);

        $query = Specialite::with([
            'creator:id,email,nom_utilisateur', 'editor:id,email,nom_utilisateur'
        ])
            ->when($request->has('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when(trim($request->search), function ($q, $search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('nom_specialite', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%");
                });
            });

        $specialites = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $specialites->items(),
            'current_page' => $specialites->currentPage(),
            'last_page'    => $specialites->lastPage(),
            'total'        => $specialites->total(),
        ]);
    }
    /**
     * Store a newly created resource in storage.
     * @permission SpecialiteController::store
     * @permission_desc Créer une spécialité de consultants
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_specialite' => 'required|unique:consultant_specialties,nom_specialite',
        ]);

        $auth = auth()->user();

        $specialite = Specialite::create([
            'nom_specialite' => $validated['nom_specialite'],
            'created_by' => $auth?->id,
            'updated_by' => $auth?->id,
        ]);

        return response()->json([
            'message' => 'Spécialité créée avec succès',
            'data' => $specialite
        ], 201);
    }

    /**
     * Display the specified resource.
     * @permission SpecialiteController::show
     * @permission_desc Afficher les détails d'une spécialité
     */
    public function show(Specialite $specialite)
    {
        $specialite->load([
            'creator:id,email,nom_utilisateur',
            'editor:id,email,nom_utilisateur'
        ]);

        return response()->json([
            'data' => $specialite
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @permission SpecialiteController::update
     * @permission_desc Mettre à jour une spécialité de consultants
     */
    public function update(Request $request, $id)
    {
        $specialite = Specialite::find($id);

        if (!$specialite) {
            return response()->json([
                'message' => 'Spécialité introuvable ou ID manquant dans la requête.'
            ], 404);
        }

        $validated = $request->validate([
            'nom_specialite' => [
                'required',
                Rule::unique('consultant_specialties', 'nom_specialite')->ignore($specialite->id),
            ],
            'is_active' => 'sometimes|boolean',
        ]);

        $auth = auth()->user();
        $specialite->update([
            'nom_specialite' => $validated['nom_specialite'],
            'is_active' => $validated['is_active'] ?? $specialite->is_active,
            'updated_by' => $auth?->id,
        ]);
        $specialite->load(['creator:id,email,nom_utilisateur', 'editor:id,email,nom_utilisateur']);

        return response()->json([
            'message' => 'Spécialité mise à jour avec succès',
            'data' => $specialite
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @permission SpecialiteController::updateStatus
     * @permission_desc Activer/Désactiver une spécialité de consultants
     */
    public function updateStatus(Specialite $specialite)
    {
        $auth = auth()->user();

        $specialite->update([
            'is_active' => !$specialite->is_active,
            'updated_by' => $auth?->id,
        ]);

        return response()->json([
            'message' => 'Statut de la spécialité mis à jour avec succès',
            'data' => $specialite
        ]);
    }

}
