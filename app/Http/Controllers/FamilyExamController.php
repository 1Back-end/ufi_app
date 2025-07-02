<?php

namespace App\Http\Controllers;

use App\Http\Requests\FamilyExamRequest;
use App\Models\FamilyExam;
use Illuminate\Http\JsonResponse;

class FamilyExamController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @permission FamilyExamController::index
     * @permission_desc Afficher la liste des familles d'examen
     */
    public function index()
    {
        return response()->json(FamilyExam::all());
    }

    /**
     * @param FamilyExamRequest $request
     * @return JsonResponse
     *
     * @permission FamilyExamController::store
     * @permission_desc Ajouter une famille d'examen
     */
    public function store(FamilyExamRequest $request)
    {
        FamilyExam::create($request->validated());

        return response()->json([
            'message' => 'Famille de examen créée',
        ], 201);
    }

    /**
     * @param FamilyExamRequest $request
     * @param FamilyExam $familyExam
     * @return JsonResponse
     *
     * @permission FamilyExamController::update
     * @permission_desc Modifier une famille d'examen
     */
    public function update(FamilyExamRequest $request, FamilyExam $familyExam)
    {
        $familyExam->update($request->validated());

        return response()->json([
            'message' => 'Famille de examen modifier',
        ], 202);
    }

    /**
     * @param FamilyExam $familyExam
     * @return JsonResponse
     *
     * @permission FamilyExamController::destroy
     * @permission_desc Supprimer une famille d'examen
     */
    public function destroy(FamilyExam $familyExam)
    {
        $familyExam->delete();

        return response()->json();
    }
}
