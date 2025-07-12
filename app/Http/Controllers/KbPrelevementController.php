<?php

namespace App\Http\Controllers;

use App\Http\Requests\KbPrelevementRequest;
use App\Models\KbPrelevement;
use Illuminate\Http\JsonResponse;

class KbPrelevementController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @permission KbPrelevementController::index
     * @permission_desc Afficher la liste des prélèvements.
     */
    public function index()
    {
        return response()->json(
            KbPrelevement::all()
        );
    }

    /**
     * @param KbPrelevementRequest $request
     * @return JsonResponse
     *
     * @permission KbPrelevementController::store
     * @permission_desc Enregistrement d’un prélèvement.
     */
    public function store(KbPrelevementRequest $request)
    {
        KbPrelevement::create($request->validated());

        return response()->json([
            "message" => __("Opération effectué avec succès !")
        ], 201);
    }

    /**
     * @param KbPrelevementRequest $request
     * @param KbPrelevement $kbPrelevement
     * @return JsonResponse
     *
     * @permission KbPrelevementController::update
     * @permission_desc Mise à jour d’un prélèvement.
     */
    public function update(KbPrelevementRequest $request, KbPrelevement $kbPrelevement)
    {
        $kbPrelevement->update($request->validated());

        return response()->json([
            'message' => __("Opération effectué avec succès !")
        ], 202);
    }

    /**
     * @param KbPrelevement $kbPrelevement
     * @return JsonResponse
     *
     * @permission KbPrelevementController::destroy
     * @permission_desc Supprimer un prélèvement.
     */
    public function destroy(KbPrelevement $kbPrelevement)
    {
        $kbPrelevement->delete();

        return response()->json();
    }
}
