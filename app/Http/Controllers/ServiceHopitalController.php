<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service_Hopital;
use App\Models\User;
use App\Models\Consultant;
use Illuminate\Validation\Rule;


/**
 * @permission_category Gestion des services hospitaliers
 * @permission_module Paramètres Applicatifs
 */
class ServiceHopitalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @permission ServiceHopitalController::index
     * @permission_desc Afficher la liste des services d'hôpitaux
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('limit', 25);
        $page    = $request->integer('page', 1);

        $query = Service_Hopital::with([
            'creator:id,email,nom_utilisateur',
            'editor:id,email,nom_utilisateur'
        ])
            ->when($request->has('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when(trim($request->search), function ($q, $search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('nom_service_hopi', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%");
                });
            });

        $services = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $services->items(),
            'current_page' => $services->currentPage(),
            'last_page'    => $services->lastPage(),
            'total'        => $services->total(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @permission ServiceHopitalController::store
     * @permission_desc Enregistrer un nouveau service d'hôpital
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_service_hopi' => 'required|string|max:255|unique:consultants_service_hospitals,nom_service_hopi',
            'is_active'        => 'sometimes|boolean',
        ]);

        $auth = auth()->user();

        $service = Service_Hopital::create([
            'nom_service_hopi' => $validated['nom_service_hopi'],
            'is_active'        => $validated['is_active'] ?? true,
            'created_by'       => $auth?->id,
            'updated_by'       => $auth?->id,
        ]);

        $service->load([
            'creator:id,email,nom_utilisateur',
            'editor:id,email,nom_utilisateur'
        ]);

        return response()->json([
            'message' => 'Service d\'hôpital créé avec succès',
            'data'    => $service
        ], 201);
    }


    /**
     * Update the specified resource in storage.
     *
     * @permission ServiceHopitalController::update
     * @permission_desc Modifier un service d'hôpital
     */
    public function update(Request $request, $id)
    {
        $service = Service_Hopital::find($id);

        if (!$service) {
            return response()->json([
                'message' => 'Service d\'hôpital introuvable.'
            ], 404);
        }

        $validated = $request->validate([
            'nom_service_hopi' => [
                'required',
                'string',
                'max:255',
                Rule::unique('consultants_service_hospitals', 'nom_service_hopi')->ignore($service->id),
            ],
            'is_active' => 'sometimes|boolean',
        ]);

        $auth = auth()->user();

        $service->update([
            'nom_service_hopi' => $validated['nom_service_hopi'],
            'is_active'        => $validated['is_active'] ?? $service->is_active,
            'updated_by'       => $auth?->id,
        ]);

        $service->load([
            'creator:id,email,nom_utilisateur',
            'editor:id,email,nom_utilisateur'
        ]);

        return response()->json([
            'message' => 'Service d\'hôpital mis à jour avec succès',
            'data'    => $service
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @permission ServiceHopitalController::show
     * @permission_desc Afficher les détails d'un service d'hôpital
     */
    public function show($id)
    {
        $service = Service_Hopital::with([
            'creator:id,email,nom_utilisateur',
            'editor:id,email,nom_utilisateur'
        ])->find($id);

        if (!$service) {
            return response()->json([
                'message' => 'Service d\'hôpital introuvable.'
            ], 404);
        }

        return response()->json([
            'data' => $service
        ]);
    }
    /**
     * Update the status (active/inactive) of the specified resource.
     *
     * @permission ServiceHopitalController::updateStatus
     * @permission_desc Modifier le statut d'un service d'hôpital
     */
    public function updateStatus(Request $request, $id)
    {
        $service = Service_Hopital::find($id);

        if (!$service) {
            return response()->json([
                'message' => 'Service d\'hôpital introuvable.'
            ], 404);
        }

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $auth = auth()->user();

        $service->update([
            'is_active'  => $validated['is_active'],
            'updated_by' => $auth?->id,
        ]);

        $service->load([
            'creator:id,email,nom_utilisateur',
            'editor:id,email,nom_utilisateur'
        ]);

        return response()->json([
            'message' => 'Statut du service d\'hôpital mis à jour avec succès',
            'data'    => $service
        ]);
    }
}
