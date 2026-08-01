<?php

namespace App\Http\Controllers;

use App\Models\DossierLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use phpDocumentor\Reflection\Location;

/**
 * @permission_category Gestion des emplacements des dossiers des patients
 * @permission_module Gestion des prestations
 */
class DossierLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission DossierLocationController::index
     * @permission_desc Afficher la liste emplacements des dossiers des patients
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('limit', 5);
        $page    = $request->integer('page', 1);
        $query = DossierLocation::with(['creator:id,email,nom_utilisateur', 'editor:id,email,nom_utilisateur'])

            ->when($request->has('is_active'), fn($q) => $q->where('is_active', $request->is_active))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when(trim($request->search), function ($q, $search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('room', 'like', "%{$search}%")
                        ->orWhere('shelf', 'like', "%{$search}%");
                });
            });
        $locations = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $locations->items(),
            'current_page' => $locations->currentPage(),
            'last_page'    => $locations->lastPage(),
            'total'        => $locations->total(),
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission DossierLocationController::store
     * @permission_desc Créer un emplacement des dossiers des patients
     */
    public function store(Request $request)
    {
        $centreId = $request->header('centre_id');

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:255|unique:dossier_locations,code',
            'room'        => 'nullable|string|max:255',
            'shelf'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $location = DossierLocation::create([
            'name'        => $request->name,
            'code'        => $request->code,
            'room'        => $request->room,
            'shelf'       => $request->shelf,
            'description' => $request->description,
            'is_active'   => $request->input('is_active', true),
            'centre_id'   => $centreId,
            'created_by'  => $request->user()?->id,
            'updated_by'  => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Emplacement créé avec succès.',
            'data'    => $location
        ], 201);
    }

    /**
     * Display a listing of the resource.
     * @permission DossierLocationController::update
     * @permission_desc Modifier un emplacement des dossiers des patients
     */
    public function update(Request $request, $id)
    {
        $centreId = $request->header('centre_id');

        $location = DossierLocation::when($centreId, fn($q) => $q->where('centre_id', $centreId))
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'        => 'sometimes|required|string|max:255',
            'code'        => 'sometimes|required|string|max:255|unique:dossier_locations,code,' . $location->id,
            'room'        => 'nullable|string|max:255',
            'shelf'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $location->update(array_merge(
            $request->only(['name', 'code', 'room', 'shelf', 'description', 'status']),
            [
                'updated_by' => $request->user()?->id,
                'centre_id'  => $centreId ?? $location->centre_id,
            ]
        ));

        return response()->json([
            'message' => 'Emplacement mis à jour avec succès.',
            'data'    => $location
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission DossierLocationController::updateStatus
     * @permission_desc Activer/Désactiver un emplacement des dossiers des patients
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $location = DossierLocation::with(['creator', 'editor'])->findOrFail($id);

        $location->update([
            'is_active' => $request->is_active,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $location->is_active
                ? 'Emplacement activé avec succès.'
                : 'Emplacement désactivé avec succès.',
            'data' => $location
        ]);
    }
}
