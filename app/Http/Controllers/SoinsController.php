<?php

namespace App\Http\Controllers;

use App\Models\Assurable;
use App\Models\Assureur;
use App\Models\Soins;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SoinsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission SoinsController::index
     * @permission_desc Afficher la liste des soins
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 10);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante
        $search = $request->input('search');

        $soins = Soins::with(['type_soins:id,name'])
            ->where('is_deleted', false)
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            })
            ->latest()
            ->paginate(perPage: $perPage, page: $page);

        return response()->json([
            'data' => $soins->items(),
            'current_page' => $soins->currentPage(),  // Page courante
            'last_page' => $soins->lastPage(),  // Dernière page
            'total' => $soins->total(),  // Nombre total d'éléments
        ]);
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Display a listing of the resource.
     * @permission SoinsController::store
     * @permission_desc Créer des soins
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        try {
            $data = $request->validate([
                'type_soin_id' => 'exists:type_soins,id',
                'pu' => 'required|integer', // prix réel
                'pu_default' => 'required|integer', // Prix par défaut pour les assureurs
                'name' => 'required|string|unique:soins,name',
            ]);

            $data['created_by'] = $auth->id;
            $soin = Soins::create($data);
            return response()->json([
                'data' => $soin,
                'message' => 'Soin enregistré avec succès'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission SoinsController::show
     * @permission_desc Afficher les détails des soins
     */
    public function show(string $id)
    {
        try {
            $soins = Soins::where('is_deleted', false)
                ->with([
                    'type_soin_id:id,name',
                    'createdBy:id,login',
                    'updatedBy:id,login'
                ])
                ->findOrFail($id);

            return response()->json([
                'data' => $soins,
                'message' => 'Détails du soin récupérés avec succès.'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Soin non trouvée.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue.',
                'message' => $e->getMessage()
            ], 500);
        }
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Display a listing of the resource.
     * @permission SoinsController::show
     * @permission_desc Mettre à jour des soins
     */
    public function update(Request $request, string $id)
    {
        $auth = auth()->user();

        try {
            $data = $request->validate([
                'type_soin_id' => 'exists:type_soins,id',
                'pu' => 'required|numeric',
                'name' => 'required|string',
                'status' => 'nullable|string|in:Actif,Inactif',
            ]);

            $soins = Soins::where('is_deleted', false)->findOrFail($id);
            $data['updated_by'] = $auth->id;

            $soins->update($data);

            return response()->json([
                'data' => $soins,
                'message' => 'Mise à jour éffectué avec succès.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Soin non trouvée'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     * @permission SoinsController::updateStatus
     * @permission_desc Changer le statut des soins
     */
    public function updateStatus(Request $request, $id, $status)
    {
        // Find the assureur by ID
        $soins = Soins::find($id);
        if (!$soins) {
            return response()->json(['message' => 'Soin non trouvé'], 404);
        }

        // Check if the assureur is deleted
        if ($soins->is_deleted) {
            return response()->json(['message' => 'Impossible de mettre à jour un soin supprimé'], 400);
        }

        // Validate the status
        if (!in_array($status, ['Actif', 'Inactif'])) {
            return response()->json(['message' => 'Statut invalide'], 400);
        }

        // Update the status
        $soins->status = $status;  // Ensure the correct field name
        $soins->save();

        // Return the updated assureur
        return response()->json([
            'message' => 'Statut mis à jour avec succès',
            'soins' => $soins // Corrected to $assureur
        ], 200);
    }
    /**
     * Display a listing of the resource.
     * @permission SoinsController::destroy
     * @permission_desc Supprimer des soins
     */
    public function destroy(string $id)
    {
        try {
            $soins = Soins::where('is_deleted', false)->findOrFail($id);
            // Marquer comme supprimé (soft delete)
            $soins->is_deleted = true;
            $soins->save();

            return response()->json([
                'message' => 'Suppression éffectué avec succès.'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Soin non trouvée.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue lors de la suppression.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
