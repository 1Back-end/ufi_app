<?php

namespace App\Http\Controllers;

use App\Models\Consultant;
use Illuminate\Http\Request;
use App\Models\Hopital;
use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * @permission_category Gestion des hôpitaux
 * @permission_module Paramètres Applicatifs
 */
class HopitalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @permission HopitalController::index
     * @permission_desc Afficher la liste des hôpitaux des consultants
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('limit', 25);
        $page    = $request->integer('page', 1);

        $query = Hopital::with([
            'creator:id,email,nom_utilisateur', 'editor:id,email,nom_utilisateur'
        ])
            ->when($request->has('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when(trim($request->search), function ($q, $search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('nom_hopi', 'like', "%{$search}%")
                        ->orWhere('Abbreviation_hopi', 'like', "%{$search}%")
                        ->orWhere('addresse_hopi', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%");
                });
            });

        $hopitaux = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $hopitaux->items(),
            'current_page' => $hopitaux->currentPage(),
            'last_page'    => $hopitaux->lastPage(),
            'total'        => $hopitaux->total(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @permission HopitalController::store
     * @permission_desc Enregistrer un nouvel hôpital des consultants
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_hopi'          => 'required|string|max:255|unique:consultants_hospitals,nom_hopi',
            'Abbreviation_hopi' => 'nullable|string|max:50',
            'addresse_hopi'     => 'nullable|string|max:255',
            'is_active'         => 'sometimes|boolean',
        ]);

        $auth = auth()->user();

        $hopital = Hopital::create([
            'nom_hopi'          => $validated['nom_hopi'],
            'Abbreviation_hopi' => $validated['Abbreviation_hopi'] ?? null,
            'addresse_hopi'     => $validated['addresse_hopi'] ?? null,
            'is_active'         => $validated['is_active'] ?? true,
            'created_by'        => $auth?->id,
            'updated_by'        => $auth?->id,
        ]);

        $hopital->load([
            'creator:id,email,nom_utilisateur',
            'editor:id,email,nom_utilisateur'
        ]);

        return response()->json([
            'message' => 'Hôpital créé avec succès',
            'data'    => $hopital
        ], 201);
    }
    /**
     * Update the specified resource in storage.
     *
     * @permission HopitalController::update
     * @permission_desc Modifier un hôpital des consultants
     */
    public function update(Request $request, $id)
    {
        $hopital = Hopital::find($id);

        if (!$hopital) {
            return response()->json([
                'message' => 'Hôpital introuvable.'
            ], 404);
        }

        $validated = $request->validate([
            'nom_hopi' => [
                'required',
                'string',
                'max:255',
                Rule::unique('consultants_hospitals', 'nom_hopi')->ignore($hopital->id),
            ],
            'Abbreviation_hopi' => 'nullable|string|max:50',
            'addresse_hopi'     => 'nullable|string|max:255',
            'is_active'         => 'sometimes|boolean',
        ]);

        $auth = auth()->user();

        $hopital->update([
            'nom_hopi'          => $validated['nom_hopi'],
            'Abbreviation_hopi' => $validated['Abbreviation_hopi'] ?? $hopital->Abbreviation_hopi,
            'addresse_hopi'     => $validated['addresse_hopi'] ?? $hopital->addresse_hopi,
            'is_active'         => $validated['is_active'] ?? $hopital->is_active,
            'updated_by'        => $auth?->id,
        ]);

        $hopital->load([
            'creator:id,email,nom_utilisateur',
            'editor:id,email,nom_utilisateur'
        ]);

        return response()->json([
            'message' => 'Hôpital mis à jour avec succès',
            'data'    => $hopital
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @permission HopitalController::show
     * @permission_desc Afficher les détails d'un hôpital des consultants
     */
    public function show($id)
    {
        $hopital = Hopital::with([
            'creator:id,email,nom_utilisateur',
            'editor:id,email,nom_utilisateur'
        ])->find($id);

        if (!$hopital) {
            return response()->json([
                'message' => 'Hôpital introuvable.'
            ], 404);
        }

        return response()->json([
            'data' => $hopital
        ]);
    }

    /**
     * Update the status (active/inactive) of the specified resource.
     *
     * @permission HopitalController::updateStatus
     * @permission_desc Modifier le statut d'un hôpital des consultants
     */
    public function updateStatus(Request $request, $id)
    {
        $hopital = Hopital::find($id);

        if (!$hopital) {
            return response()->json([
                'message' => 'Hôpital introuvable.'
            ], 404);
        }

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $auth = auth()->user();

        $hopital->update([
            'is_active'  => $validated['is_active'],
            'updated_by' => $auth?->id,
        ]);

        $hopital->load([
            'creator:id,email,nom_utilisateur',
            'editor:id,email,nom_utilisateur'
        ]);

        return response()->json([
            'message' => "Statut de l'hôpital mis à jour avec succès",
            'data'    => $hopital
        ]);
    }

}
