<?php

namespace App\Http\Controllers;

use App\Models\OpsTblReferreMedical;
use Illuminate\Http\Request;

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
     * @permission OpsTblReferreMedicalController::store
     * @permission_desc Création des referres médicals
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $request->validate([
            'rapport_consultations_id' => 'required|exists:ops_tbl_rapport_consultations,id',
            'description' => 'required|string',
            'code_prescripteur' => 'required|string',
        ]);

        $result = OpsTblReferreMedical::create([
            'rapport_consultations_id' => $request->rapport_consultations_id,
            'description' => $request->description,
            'code_prescripteur' => $request->code_prescripteur,
            'created_by' => $auth->id,
        ]);
        return response()->json([
            'result' => $result,
            "message" => "Enregistrement éffectué avec succès"
        ]);

        //
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
