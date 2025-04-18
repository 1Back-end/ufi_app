<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 10);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante

        // Récupérer les assureurs avec pagination
        $consultations = Consultation::where('is_deleted', false)
            ->with(
                'typeconsultation:id,name',
            )
            ->paginate($perPage);

        return response()->json([
            'data' => $consultations->items(),
            'current_page' => $consultations->currentPage(),  // Page courante
            'last_page' => $consultations->lastPage(),  // Dernière page
            'total' => $consultations->total(),  // Nombre total d'éléments
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        try {
            $data = $request->validate([
                'typeconsultation_id'=> 'exists:typeconsultations,id',
                'pu_nonassure'=>'required',
                'pu_assure'=>'required',
            ]);
            $data['created_by'] = $auth->id;
            $consultation = Consultation::create($data);
            return response()->json([
                'data' => $consultation,
                'message' => 'Enregistrement effectué avec succès'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Erreur de validation
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Erreur générale
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $consultation = Consultation::where('is_deleted', false)
                ->with([
                    'typeconsultation:id,name',
                    'createdBy:id,login',
                    'updatedBy:id,login'
                ])
                ->findOrFail($id);

            return response()->json([
                'data' => $consultation,
                'message' => 'Détails de la consultation récupérés avec succès.'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Consultation non trouvée.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $auth = auth()->user();

        try {
            $data = $request->validate([
                'typeconsultation_id' => 'exists:typeconsultations,id',
                'pu_nonassure' => 'required|numeric',
                'pu_assure' => 'required|numeric',
                'status' => 'nullable|string|in:Actif,Inactif',
            ]);

            $consultation = Consultation::where('is_deleted', false)->findOrFail($id);
            $data['updated_by'] = $auth->id;

            $consultation->update($data);

            return response()->json([
                'data' => $consultation,
                'message' => 'Consultation mise à jour avec succès.'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Consultation non trouvée'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id, $status)
    {
        // Find the assureur by ID
        $consultations = Consultation::find($id);
        if (!$consultations) {
            return response()->json(['message' => 'Consultation non trouvé'], 404);
        }

        // Check if the assureur is deleted
        if ($consultations->is_deleted) {
            return response()->json(['message' => 'Impossible de mettre à jour une consultation supprimé'], 400);
        }

        // Validate the status
        if (!in_array($status, ['Actif', 'Inactif'])) {
            return response()->json(['message' => 'Statut invalide'], 400);
        }

        // Update the status
        $consultations->status = $status;  // Ensure the correct field name
        $consultations->save();

        // Return the updated assureur
        return response()->json([
            'message' => 'Statut mis à jour avec succès',
            'consultations' => $consultations  // Corrected to $assureur
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $consultation = Consultation::where('is_deleted', false)->findOrFail($id);

            // Marquer comme supprimé (soft delete)
            $consultation->is_deleted = true;
            $consultation->save();

            return response()->json([
                'message' => 'Consultation supprimée avec succès.'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Consultation non trouvée.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue lors de la suppression.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
