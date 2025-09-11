<?php

namespace App\Http\Controllers;

use App\Http\Requests\ElementResultRequest;
use App\Models\ElementResult;
use Illuminate\Http\JsonResponse;

/**
 * @permission_category Gestion des éléments de resultat
 */
class ElementResultController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @permission ElementResultController::index
     * @permission_desc Liste des éléments de résultat
     */
    public function index()
    {
        return response()->json(ElementResult::with([])->get());
    }

    /**
     * @param ElementResultRequest $request
     * @return JsonResponse
     *
     * @permission ElementResultController::store
     * @permission_desc Ajouter un élément de résultat
     */
    public function store(ElementResultRequest $request)
    {
        ElementResult::create($request->validated());

        return response()->json([
            'message' => 'ElementResult created successfully'
        ], 201);
    }

    /**
     * @param ElementResultRequest $request
     * @param ElementResult $elementResult
     * @return JsonResponse
     *
     * @permission ElementResultController::update
     * @permission_desc Modifier un élément de résultat
     */
    public function update(ElementResultRequest $request, ElementResult $elementResult)
    {
        $elementResult->update($request->validated());

        return response()->json([
            'message' => 'ElementResult updated successfully'
        ], 202);
    }

    /**
     * @param ElementResult $elementResult
     * @return JsonResponse
     *
     * @permission ElementResultController::destroy
     * @permission_desc Supprimer un élément de résultat
     */
    public function destroy(ElementResult $elementResult)
    {
        $elementResult->delete();

        return response()->json();
    }
}
