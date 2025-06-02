<?php

namespace App\Http\Controllers;

use App\Models\Assurable;
use App\Models\Assureur;
use App\Models\Consultation;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class ConsultationController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission ConsultationController::index
     * @permission_desc Afficher les consultations avec pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 10);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante

        // Récupérer les assureurs avec pagination
        $consultations = Consultation::where('is_deleted', false)
            ->when($request->input('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where('name', 'like', "%{$search}%");
            })
            ->with('typeconsultation:id,name')
            ->latest()
            ->paginate(perPage: $perPage, page: $page);

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
     * Display a listing of the resource.
     * @permission ConsultationController::store
     * @permission_desc Créer une pagination
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
            $data = $request->validate([
                'typeconsultation_id'=> ['required', 'exists:typeconsultations,id'],
                'pu_default' => 'required|integer', // Prix par défaut pour les assurances
                'pu' => [
                    'required',
                    'integer',
                    Rule::unique('consultations')->where(function ($query) use ($request) {
                        return $query->where('name', $request->name);
                    }),
                ],
                'name' => 'required|string|unique:consultations,name',
                'validation_date' => 'required|integer',
            ]);
            $data['created_by'] = $auth->id;
            $consultation = Consultation::create($data);

            return response()->json([
                'data' => $consultation,
                'message' => 'Consultation enregistrée succès'
            ]);

        //
    }

    /**
     * Display a listing of the resource.
     * @permission ConsultationController::show
     * @permission_desc Afficher les informations d'une consultation
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
     * Display a listing of the resource.
     * @permission ConsultationController::update
     * @permission_desc Mettre à jour une consultation
     */
    public function update(Request $request, string $id)
    {
        $auth = auth()->user();

        // Validation des données de la requête
        $data = $request->validate([
            'typeconsultation_id' => 'required|exists:typeconsultations,id',
            'pu' => [
                'required',
                'integer',
                Rule::unique('consultations')->where(function ($query) use ($request) {
                    return $query->where('name', $request->name);
                })->ignore($id), // Ignore l'enregistrement actuel
            ],
            'name' => [
                'required',
                'string',
                Rule::unique('consultations', 'name')->ignore($id), // Ignore l'enregistrement actuel
            ],
            'validation_date' => 'required|integer',
        ]);

        // Récupération de la consultation à mettre à jour
        $consultation = Consultation::where('is_deleted', false)->findOrFail($id);

        // Ajout de l'ID de l'utilisateur qui a effectué la mise à jour
        $data['updated_by'] = $auth->id;

        // Mise à jour des données
        $consultation->update($data);

        return response()->json([
            'data' => $consultation,
            'message' => 'Consultation mise à jour avec succès.'
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ConsultationController::updateStatus
     * @permission_desc Changer le statut d'une consultation
     */
    public function updateStatus(Request $request, $id, $status)
    {
        // Find the assureur by ID
        $consultations = Consultation::find($id);
        if (!$consultations) {
            return response()->json(['message' => 'Consultation non trouvé'], 404);
        }

        // Check if the assureur is deleted
        if ($consultations->is_deleted) {
            return response()->json([ ], 400);
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
     * Display a listing of the resource.
     * @permission ConsultationController::destroy
     * @permission_desc Supprimer une consultation
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
