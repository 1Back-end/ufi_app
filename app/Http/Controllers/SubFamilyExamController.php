<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubFamilyExamRequest;
use App\Models\SubFamilyExam;
use Illuminate\Http\JsonResponse;

class SubFamilyExamController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @permission SubFamilyExamController::index
     * @permission_desc Afficher la liste des sous familles d'examen
     */
    public function index()
    {
        return response()->json(SubFamilyExam::with(["familyExam"])->get());
    }


    /**
     * @param SubFamilyExamRequest $request
     * @return JsonResponse
     *
     * @permission SubFamilyExamController::store
     * @permission_desc Creer un examen familial
     */
    public function store(SubFamilyExamRequest $request)
    {
        SubFamilyExam::create($request->validated());

        return response()->json([
            'message' => 'Examen familial créé avec succès'
        ], 201);
    }

    /**
     * @param SubFamilyExamRequest $request
     * @param SubFamilyExam $subFamilyExam
     * @return JsonResponse
     *
     * @permission SubFamilyExamController::update
     * @permission_desc Mettre à jour un examen familial
     */
    public function update(SubFamilyExamRequest $request, SubFamilyExam $subFamilyExam)
    {
        $subFamilyExam->update($request->validated());

        return response()->json([
            'message' => 'Examen familial mis à jour avec success'
        ], 202);
    }


    /**
     * @param SubFamilyExam $subFamilyExam
     * @return JsonResponse
     *
     * @permission SubFamilyExamController::destroy
     * @permission_desc Supprimer un examen familial
     */
    public function destroy(SubFamilyExam $subFamilyExam)
    {
        $subFamilyExam->delete();

        return response()->json();
    }
}
