<?php

namespace App\Http\Controllers;

use App\Http\Requests\TubePrelevementRequest;
use App\Models\TubePrelevement;
use Illuminate\Http\JsonResponse;

/**
 * @permission_category Gestion des tubes de prélèvements
 */

class TubePrelevementController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @permission TubePrelevementController::index
     * @permission_desc Liste des tube prélèvements
     */
    public function index()
    {
        return response()->json(TubePrelevement::all());
    }

    /**
     * @param TubePrelevementRequest $request
     * @return JsonResponse
     *
     * @permission TubePrelevementController::store
     * @permission_desc Créer un tube prélèvement
     */
    public function store(TubePrelevementRequest $request)
    {
        TubePrelevement::create($request->validated());

        return response()->json([
            'message' => 'Tube prélèvement créé'
        ], 201);
    }

    /**
     * @param TubePrelevementRequest $request
     * @param TubePrelevement $tubePrelevement
     * @return JsonResponse
     *
     * @permission TubePrelevementController::update
     * @permission_desc Modifier un tube prélèvement
     */
    public function update(TubePrelevementRequest $request, TubePrelevement $tubePrelevement)
    {
        $tubePrelevement->update($request->validated());

        return response()->json([
            'message' => 'Tube prélèvement mis à jour'
        ], 202);
    }

    /**
     * @param TubePrelevement $tubePrelevement
     * @return JsonResponse
     *
     * @permission TubePrelevementController::destroy
     * @permission_desc Supprimer un tube prélèvement
     */
    public function destroy(TubePrelevement $tubePrelevement)
    {
        $tubePrelevement->delete();

        return response()->json();
    }
}
