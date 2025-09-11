<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryElementResultRequest;
use App\Models\CategoryElementResult;
use Illuminate\Http\JsonResponse;

/**
 * @permission_category Gestion des catégories d'éléments de résultat
 */
class CategoryElementResultController extends Controller
{
    /**
     * Afficher la liste des catégories d'éléments de résultat
     *
     * @permission CategoryElementResultController::index
     * @permission_desc Afficher la liste des catégories d'éléments de résultat
     *
     * @return JsonResponse
     */
    public function index()
    {
        return response()->json(CategoryElementResult::all());
    }

    /**
     * @permission CategoryElementResultController::store
     * @permission_desc Ajouter une catégorie d'élément de résultat
     *
     * @param CategoryElementResultRequest $request
     * @return JsonResponse
     */
    public function store(CategoryElementResultRequest $request)
    {
        CategoryElementResult::create($request->validated());

        return response()->json([
            'message' => "Catégorie d'élément de résultat ajoutée",
        ], 201);
    }

    /**
     * Mettre à jour une catégorie d'élément de résultat
     *
     * @permission CategoryElementResultController::update
     * @permission_desc Mettre à jour une catégorie d'élément de résultat
     *
     * @param CategoryElementResultRequest $request
     * @param CategoryElementResult $categoryElementResult
     * @return JsonResponse
     */
    public function update(CategoryElementResultRequest $request, CategoryElementResult $categoryElementResult)
    {
        $categoryElementResult->update($request->validated());

        return response()->json([
            'message' => "Catégorie d'élément de résultat modifiée",
        ]);
    }

    /**
     * Supprimer une catégorie d'élément de résultat
     *
     * @permission CategoryElementResultController::destroy
     * @permission_desc Supprimer une catégorie d'élément de résultat
     */
    public function destroy(CategoryElementResult $categoryElementResult)
    {
        $categoryElementResult->delete();

        return response()->json();
    }
}
