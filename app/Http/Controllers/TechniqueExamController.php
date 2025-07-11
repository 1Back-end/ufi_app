<?php

namespace App\Http\Controllers;

use App\Http\Requests\TechniqueExamRequest;
use App\Models\TechniqueExam;
use Illuminate\Http\JsonResponse;

class TechniqueExamController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @permission TechniqueExamController::index
     * @permission_desc Permet d'afficher la liste des analyses techniques
     */
    public function index()
    {
        return response()->json(TechniqueExam::with(['analysisTechnique', 'examen'])->get());
    }

    /**
     * @param TechniqueExamRequest $request
     * @return JsonResponse
     *
     * @permission TechniqueExamController::store
     * @permission_desc Permet d'ajouter une analyse technique
     */
    public function store(TechniqueExamRequest $request)
    {
        TechniqueExam::create($request->validated());

        return response()->json([
            'message' => 'L\'analyse technique a bien été ajoutée.'
        ], 201);
    }

    /**
     * @param TechniqueExamRequest $request
     * @param TechniqueExam $techniqueExam
     * @return JsonResponse
     *
     * @permission TechniqueExamController::update
     * @permission_desc Permet de modifier une analyse technique
     */
    public function update(TechniqueExamRequest $request, TechniqueExam $techniqueExam)
    {
        $techniqueExam->update($request->validated());

        return response()->json([
            'message' => 'L\'analyse technique a bien été modifiée.'
        ], 202);
    }

    /**
     * @param TechniqueExam $techniqueExam
     * @return JsonResponse
     *
     * @permission TechniqueExamController::destroy
     * @permission_desc Permet de supprimer une analyse technique
     */
    public function destroy(TechniqueExam $techniqueExam)
    {
        $techniqueExam->delete();

        return response()->json();
    }
}
