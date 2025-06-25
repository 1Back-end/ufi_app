<?php

namespace App\Http\Controllers;

use App\Http\Requests\TypeResultRequest;
use App\Models\TypeResult;
use Illuminate\Http\JsonResponse;

class TypeResultController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @permission TypeResultController::index
     * @permission_desc Afficher la liste des types de résultat.
     */
    public function index()
    {
        return response()->json(TypeResult::all());
    }

    /**
     * @param TypeResultRequest $request
     * @return JsonResponse
     *
     * @permission TypeResultController::store
     * @permission_desc Créer un type de résultat
     */
    public function store(TypeResultRequest $request)
    {
        TypeResult::create($request->validated());

        return response()->json([
            'message' => 'Type de résultat créé',
        ], 201);
    }

    /**
     * @param TypeResultRequest $request
     * @param TypeResult $typeResult
     * @return JsonResponse
     *
     * @permission TypeResultController::update
     * @permission_desc Mettre à jour un type de résultat
     */
    public function update(TypeResultRequest $request, TypeResult $typeResult)
    {
        $typeResult->update($request->validated());

        return response()->json([
            'message' => 'Type de résultat mis à jour',
        ], 202);
    }

    /**
     * @param TypeResult $typeResult
     * @return JsonResponse
     *
     * @permission TypeResultController::destroy
     * @permission_desc Supprimer un type de résultat
     */
    public function destroy(TypeResult $typeResult)
    {
        $typeResult->delete();

        return response()->json();
    }
}
