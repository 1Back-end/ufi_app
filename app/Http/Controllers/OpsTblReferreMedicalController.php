<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\OpsTblMiseEnObservationHospitalisation;
use App\Models\OpsTblReferreMedical;
use App\Models\Prescripteur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OpsTblReferreMedicalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Display a listing of the resource.
     * @permission OpsTblReferreMedicalController::historiqueReferresMedicaux
     * @permission_desc Afficher l'historique des referres médicals d'un client
     */
    public function historiqueReferresMedicaux(Request $request, $client_id)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        // Vérifier que le client existe
        $client = Client::find($client_id);
        if (!$client) {
            return response()->json(['message' => 'Client non trouvé'], 404);
        }

        // Requête des référés médicaux liés au client
        $query = OpsTblReferreMedical::where('is_deleted', false)
            ->whereHas('rapportConsultation.dossierConsultation.rendezVous', function ($q) use ($client_id) {
                $q->where('client_id', $client_id);
            })
            ->with([
                'rapportConsultation.dossierConsultation.rendezVous.client',
                'rapportConsultation.dossierConsultation.rendezVous.consultant',
                'rapportConsultation',
                'prescripteur',
                'creator',
                'updater',
                'consultant',
            ]);

        // Optionnel : recherche globale
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%$search%")
                    ->orWhere('code_prescripteur', 'like', "%$search%")
                    ->orWhereHas('prescripteur', function ($q2) use ($search) {
                        $q2->where('nom', 'like', "%$search%")
                            ->orWhere('prenom', 'like', "%$search%");
                    });
            });
        }

        // Paginer et retourner
        $result = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $result->items(),
            'current_page' => $result->currentPage(),
            'last_page' => $result->lastPage(),
            'total' => $result->total(),
            'message' => 'Historique des référés médicaux récupéré avec succès.',
        ]);
    }



    /**
     * Display a listing of the resource.
     * @permission OpsTblReferreMedicalController::store
     * @permission_desc Création des referres médicals
     */
    public function store(Request $request)
    {
        try {
            $auth = auth()->user();

            $validated = Validator::make($request->all(), [
                'rapport_consultations_id' => 'required|exists:ops_tbl_rapport_consultations,id',
                'description' => 'required|string',
                'type_prescripteur' => 'required|in:Prescripteur Interne,Prescripteur Externe',
                'consultant_id' => 'nullable|exists:consultants,id',
                'nom' => 'nullable|string',
                'prenom' => 'nullable|string',
                'email' => 'nullable|email',
                'telephone' => 'nullable|string',
                'adresse' => 'nullable|string',
            ])->validate();

            $codePrescripteur = OpsTblReferreMedical::generateCodePrescripteur();

            $data = [
                'rapport_consultations_id' => $request->rapport_consultations_id,
                'description' => $request->description,
                'type_prescripteur' => $request->type_prescripteur,
                'code_prescripteur' => $codePrescripteur,
                'created_by' => $auth->id,
            ];

            if ($request->type_prescripteur === 'Prescripteur Interne') {
                $data['consultant_id'] = $request->consultant_id;
            }

            if ($request->type_prescripteur === 'Prescripteur Externe') {
                $prescripteur = Prescripteur::create([
                    'nom' => $request->nom,
                    'prenom' => $request->prenom,
                    'email' => $request->email,
                    'telephone' => $request->telephone,
                    'adresse' => $request->adresse,
                    'created_by' => $auth->id,
                ]);

                $data['prescripteur_id'] = $prescripteur->id;
            }

            $result = OpsTblReferreMedical::create($data);

            return response()->json([
                'result' => $result,
                'message' => 'Enregistrement effectué avec succès',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de l\'enregistrement.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }


    /**
     * Update the specified resource in storage.
     * @permission OpsTblReferreMedicalController::update
     * @permission_desc Modification des referres médicals
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        $request->validate([
            'rapport_consultations_id' => 'required|exists:ops_tbl_rapport_consultations,id',
            'description' => 'required|string',
            'code_prescripteur' => 'required|string',
        ]);

        $referre = OpsTblReferreMedical::where('is_deleted', false)
            ->findOrFail($id);

        $referre->update([
            'rapport_consultations_id' => $request->rapport_consultations_id,
            'description' => $request->description,
            'code_prescripteur' => $request->code_prescripteur,
            'updated_by' => $auth->id,
        ]);

        return response()->json([
            'result' => $referre,
            'message' => 'Modification effectuée avec succès'
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
