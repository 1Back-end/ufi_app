<?php

namespace App\Http\Controllers;

use App\Models\Assurable;
use App\Models\Assureur;
use App\Models\User;
use App\Models\OpsTblHospitalisation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpsTblHospitalisationController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission OpsTblHospitalisationController::index
     * @permission_desc Afficher les hospitalisations avec pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 5);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante

        $hospitalisations = OpsTblHospitalisation::where('is_deleted', false)
            ->when($request->has('active'), function (Builder $query) {
                $query->where('status', request('active'));
            })
            ->when($request->input('search'), function (Builder $query) {
                $query->where('name', 'like', '%' . request('search') . '%');
            })
            ->latest()->paginate(perPage: $perPage, page: $page);
        return response()->json([
            'data' => $hospitalisations->items(),
            'current_page' => $hospitalisations->currentPage(),  // Page courante
            'last_page' => $hospitalisations->lastPage(),  // Dernière page
            'total' => $hospitalisations->total(),  // Nombre total d'éléments
        ]);

    }

    /**
     * Display a listing of the resource.
     * @permission OpsTblHospitalisationController::store
     * @permission_desc Créer une Hospitalisation
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        try {
            $data = $request->validate([
                'name' => 'required|string|unique:ops_tbl_hospitalisation,name',
                'pu' => 'required|numeric',
                'pu_default' => 'required|integer',
                'description' => 'nullable|string',
            ]);
            $data['created_by'] = $auth->id;
            $hospitalisation = OpsTblHospitalisation::create($data);
            return response()->json([
                'data' => $hospitalisation,
                'message' => 'Hospitalisation enregistrée avec succès.'
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
    }

    /**
     * Display a listing of the resource.
     * @permission OpsTblHospitalisationController::update
     * @permission_desc Mettre à jour une hospitalisation
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        try {
            $data = $request->validate([
                'name' => 'required|string',
                'pu' => 'required|numeric',
                'pu_default' => 'nullable|integer',
                'description' => 'nullable|string',
            ]);

            $hospitalisation = OpsTblHospitalisation::where('is_deleted', false)->findOrFail($id);
            $data['updated_by'] = $auth->id;
            $hospitalisation->update($data);

            return response()->json([
                'data' => $hospitalisation,
                'message' => 'Mise à jour effectuée avec succès'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission OpsTblHospitalisationController::updateStatus
     * @permission_desc Changer le statut d'une hospitalisation
     */
    public function updateStatus(Request $request, $id, $status)
    {
        // Find the assureur by ID
        $hospitalisation = OpsTblHospitalisation::find($id);
        if (!$hospitalisation) {
            return response()->json(['message' => 'Donnée non trouvé'], 404);
        }

        // Check if the assureur is deleted
        if ($hospitalisation->is_deleted) {
            return response()->json(['message' => 'Impossible de mettre à jour une donnée deja supprimée'], 400);
        }

        // Validate the status
        if (!in_array($status, ['Actif', 'Inactif'])) {
            return response()->json(['message' => 'Statut invalide'], 400);
        }

        // Update the status
        $hospitalisation->status = $status;  // Ensure the correct field name
        $hospitalisation->save();

        // Return the updated assureur
        return response()->json([
            'message' => 'Statut mis à jour avec succès',
            'soins' => $hospitalisation // Corrected to $assureur
        ], 200);
    }

    public function getPuByHospitalisationId($id, Request $request)
    {
        $assureurId = $request->query('assureur_id');

        // Vérifier que l’hospitalisation existe
        $hospitalisation = OpsTblHospitalisation::find($id);

        if (!$hospitalisation) {
            return response()->json(['error' => 'Hospitalisation non trouvée'], 404);
        }

        // Chercher un PU spécifique à l’assureur (si assureur_id est fourni)
        $assurable = Assurable::where('assurable_type', OpsTblHospitalisation::class)
            ->where('assurable_id', $id)
            ->when($assureurId, function ($query, $assureurId) {
                return $query->where('assureur_id', $assureurId);
            })
            ->first();

        $pu = $assurable?->pu ?? $hospitalisation->pu_default;

        return response()->json([
            'id' => $hospitalisation->id,
            'name' => $hospitalisation->name,
            'pu' => $pu,
        ]);
    }


    //
}
