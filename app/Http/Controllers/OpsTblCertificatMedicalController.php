<?php

namespace App\Http\Controllers;

use App\Models\OpsTblCertificatMedical;
use Illuminate\Http\Request;

class OpsTblCertificatMedicalController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission OpsTblCertificatMedicalController::index
     * @permission_desc Afficher la liste des certificats médicaux
     */
    public function index(){

    }
    /**
     * Display a listing of the resource.
     * @permission OpsTblCertificatMedicalController::store
     * @permission_desc Creer un certificat médical pour des dossiers de consultations
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        $request->validate([
            'type' => 'nullable|string|max:255',
            'commentaire' => 'nullable|string',
            'nbre_jour_repos' => 'nullable|integer',
            'rapport_consultation_id' => 'nullable|exists:ops_tbl_rapport_consultations,id',
        ]);

        $certificat = OpsTblCertificatMedical::create([
            'type' => $request->type,
            'commentaire' => $request->commentaire,
            'nbre_jour_repos' => $request->nbre_jour_repos,
            'rapport_consultation_id' => $request->rapport_consultation_id,
            'created_by' => $auth->id

        ]);

        return response()->json([
            'message' => 'Certificat médical enregistré avec succès.',
            'data' => $certificat
        ], 201);
    }

    /**
     * Display a listing of the resource.
     * @permission OpsTblCertificatMedicalController::update
     * @permission_desc Modifier un certificat médical pour des dossiers de consultations
     */

    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        $request->validate([
            'type' => 'nullable|string|max:255',
            'commentaire' => 'nullable|string',
            'nbre_jour_repos' => 'nullable|integer',
            'motif_consultation_id' => 'nullable|exists:ops_tbl__motif_consultations,id',
        ]);

        $certificat = OpsTblCertificatMedical::findOrFail($id);

        $certificat->update([
            'type' => $request->type,
            'commentaire' => $request->commentaire,
            'nbre_jour_repos' => $request->nbre_jour_repos,
            'motif_consultation_id' => $request->motif_consultation_id,
            'updated_by' => $auth->id,
        ]);

        return response()->json([
            'message' => 'Certificat médical mis à jour avec succès.',
            'data' => $certificat
        ], 200);
    }

    public function show($id)
    {
        $certificat = OpsTblCertificatMedical::with('motifConsultation', 'creator', 'updater')->findOrFail($id);

        return response()->json([
            'message' => 'Détails du certificat médical.',
            'data' => $certificat
        ], 200);
    }


    //
}
