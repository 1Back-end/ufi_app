<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnalysisTechniqueRequest;
use App\Models\AnalysisTechnique;
use Illuminate\Http\JsonResponse;


/**
 * @permission_category Gestion des techniques d'analyse
 */
class AnalysisTechniqueController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @permission AnalysisTechniqueController::index
     * @permission_desc Afficher la liste des techniques d'analyse
     */
    public function index()
    {
        return response()->json(
            AnalysisTechnique::all()
        );
    }

    /**
     * @param AnalysisTechniqueRequest $request
     * @return JsonResponse
     *
     * @permission AnalysisTechniqueController::store
     * @permission_desc Ajouter une technique d'analyse
     */
    public function store(AnalysisTechniqueRequest $request)
    {
        AnalysisTechnique::create($request->validated());

        return response()->json([
            "message" => "Technique d'analyse ajoutée",
        ]);
    }

    /**
     * @param AnalysisTechniqueRequest $request
     * @param AnalysisTechnique $analysisTechnique
     * @return JsonResponse
     *
     * @permission AnalysisTechniqueController::update
     * @permission_desc Mise à jour d'une technique d'analyse
     */
    public function update(AnalysisTechniqueRequest $request, AnalysisTechnique $analysisTechnique)
    {
        $analysisTechnique->update($request->validated());

        return response()->json([
            "message" => "Technique d'analyse modifiée",
        ]);
    }

    /**
     * @param AnalysisTechnique $analysisTechnique
     * @return JsonResponse
     *
     * @permission AnalysisTechniqueController::destroy
     * @permission_desc Suppression d'une technique d'analyse
     */
    public function destroy(AnalysisTechnique $analysisTechnique)
    {
        $analysisTechnique->delete();

        return response()->json();
    }
}
