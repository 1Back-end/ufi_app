<?php

namespace App\Http\Controllers;
use App\Models\Assurable;
use App\Models\Assureur;
use App\Models\User;
use App\Models\OpsTblHospitalisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpsTblHospitalisationController extends Controller
{
    public  function  index(Request $request){
        $perPage = $request->input('limit', 5);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante

        $hospitalisations = OpsTblHospitalisation::where('is_deleted', false)->paginate($perPage);
        return response()->json([
            'data' => $hospitalisations->items(),
            'current_page' => $hospitalisations->currentPage(),  // Page courante
            'last_page' => $hospitalisations->lastPage(),  // Dernière page
            'total' => $hospitalisations->total(),  // Nombre total d'éléments
        ]);

    }
    public function store(Request $request){
        $auth = auth()->user();

        try {
            $data = $request->validate([
                'name' => 'required|string|unique:ops_tbl_hospitalisation,name',
                'pu'=>'required|numeric',
                'pu_default' => 'required|integer',
                'description'=>'nullable|string',
            ]);
            $data['created_by'] = $auth->id;
            DB::beginTransaction();
            $hospitalisation = OpsTblHospitalisation::create($data);
            $assureurs = Assureur::where('is_deleted', false)->get();

            foreach ($assureurs as $assureur) {
                Assurable::updateOrInsert(
                    [
                        'assureur_id' => $assureur->id,
                        'assurable_type' => OpsTblHospitalisation::class,
                        'assurable_id' => $hospitalisation->id,
                    ],
                    [
                        'pu' => $data['pu_default'],
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'data' => $hospitalisation,
                'message' => 'Hospitalisation enregistrée avec ventilation des tarifs pour les assurances.'
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
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        try {
            $data = $request->validate([
                'name' => 'required|string',
                'pu' => 'required|numeric',
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

    //
}
