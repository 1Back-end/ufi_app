<?php

namespace App\Http\Controllers;

use App\Http\Requests\TypePrelevementRequest;
use App\Models\TypePrelevement;
use Illuminate\Http\JsonResponse;

/**
 * @permission_category Gestion des types de prélèvements
 */

class TypePrelevementController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @permission TypePrelevementController::index
     * @permission_desc Afficher la liste des types de prélèvements
     */
    public function index()
    {
        return response()->json(TypePrelevement::all());
    }

    /**
     * @param TypePrelevementRequest $request
     * @return JsonResponse
     *
     * @permission TypePrelevementController::store
     * @permission_desc Ajouter un type de prélèvement
     */
    public function store(TypePrelevementRequest $request)
    {
        TypePrelevement::create($request->validated());

        return response()->json([
            'message' => 'Type de prélèvement créé avec succès.'
        ], 201);
    }

    /**
     * @param TypePrelevementRequest $request
     * @param TypePrelevement $typePrelevement
     * @return JsonResponse
     *
     * @permission TypePrelevementController::update
     * @permission_desc Modifier un type de prélèvement
     */
    public function update(TypePrelevementRequest $request, TypePrelevement $typePrelevement)
    {
        $typePrelevement->update($request->validated());

        return response()->json([
            'message' => 'Type de prélèvement mis à jour avec succès.'
        ], 202);
    }

    /**
     * @param TypePrelevement $typePrelevement
     * @return JsonResponse
     *
     * @permission TypePrelevementController::destroy
     * @permission_desc Supprimer un type de prélèvement
     */
    public function destroy(TypePrelevement $typePrelevement)
    {
        $typePrelevement->delete();

        return response()->json();
    }
}
