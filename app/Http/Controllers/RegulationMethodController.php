<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegulationMethodRequest;
use App\Models\RegulationMethod;
use Illuminate\Http\JsonResponse;

/**
 * @permission_category Gestion des modes de règlements
 */
class RegulationMethodController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @permission RegulationMethodController::index
     * @permission_desc Liste des modes de règlement
     */
    public function index()
    {
        return response()->json(
            RegulationMethod::with(['createdBy:id,nom_utilisateur', 'updatedBy:id,nom_utilisateur'])
                ->when(request()->input('active'), function ($query) {
                    $query->where('active', request()->input('active'));
                })
                ->get()
        );
    }

    /**
     * @param RegulationMethodRequest $request
     * @return JsonResponse
     *
     * @permission RegulationMethodController::store
     * @permission_desc Créer un mode de règlement
     */
    public function store(RegulationMethodRequest $request)
    {
        RegulationMethod::create($request->validated());

        return response()->json([
            'message' => __('Enregistrement effectué avec succès')
        ]);
    }

    /**
     * @param RegulationMethodRequest $request
     * @param RegulationMethod $regulationMethod
     * @return JsonResponse
     *
     * @permission RegulationMethodController::update
     * @permission_desc Modifier un mode de règlement
     */
    public function update(RegulationMethodRequest $request, RegulationMethod $regulationMethod)
    {
        $regulationMethod->update($request->validated());

        return response()->json([
            'message' => 'Mise à jour effectuée avec succès'
        ]);
    }

    /**
     * @param RegulationMethod $regulationMethod
     * @return JsonResponse
     *
     * @permission RegulationMethodController::activate
     * @permission_desc Activer/Désactiver un mode de règlement
     */
    public function activate(RegulationMethod $regulationMethod)
    {
        $regulationMethod->active = !$regulationMethod->active;
        $regulationMethod->save();

        return response()->json([], 200);
    }
}
