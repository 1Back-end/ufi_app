<?php

namespace App\Http\Controllers;

use App\Http\Requests\ElementPaillasseExamenRequest;
use App\Models\ElementPaillasseExamen;
use Illuminate\Http\JsonResponse;

class ElementPaillasseExamenController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @permission ElementPaillasseExamenController::index
     * @permission_desc Afficher la liste des éléments de paillasse d'examen
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json(ElementPaillasseExamen::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @permission ElementPaillasseExamenController::store
     * @permission_desc Ajouter un nouvel élément de paillasse d'examen
     *
     * @param ElementPaillasseExamenRequest $request
     * @return JsonResponse
     */
    public function store(ElementPaillasseExamenRequest $request): JsonResponse
    {
        ElementPaillasseExamen::create($request->validated());

        return response()->json([
            'message' => "Élément de paillasse d'examen ajouté avec succès",
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @permission ElementPaillasseExamenController::show
     * @permission_desc Afficher un élément de paillasse d'examen spécifique
     *
     * @param ElementPaillasseExamen $elementPaillasseExamen
     * @return JsonResponse
     */
    public function show(ElementPaillasseExamen $elementPaillasseExamen): JsonResponse
    {
        return response()->json($elementPaillasseExamen);
    }

    /**
     * Update the specified resource in storage.
     *
     * @permission ElementPaillasseExamenController::update
     * @permission_desc Mettre à jour un élément de paillasse d'examen
     *
     * @param ElementPaillasseExamenRequest $request
     * @param ElementPaillasseExamen $elementPaillasseExamen
     * @return JsonResponse
     */
    public function update(ElementPaillasseExamenRequest $request, ElementPaillasseExamen $elementPaillasseExamen): JsonResponse
    {
        $elementPaillasseExamen->update($request->validated());

        return response()->json([
            'message' => "Élément de paillasse d'examen mis à jour avec succès",
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @permission ElementPaillasseExamenController::destroy
     * @permission_desc Supprimer un élément de paillasse d'examen
     *
     * @param ElementPaillasseExamen $elementPaillasseExamen
     * @return JsonResponse
     */
    public function destroy(ElementPaillasseExamen $elementPaillasseExamen): JsonResponse
    {
        $elementPaillasseExamen->delete();

        return response()->json(null, 204);
    }
}
