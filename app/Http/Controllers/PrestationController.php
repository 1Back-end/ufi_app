<?php

namespace App\Http\Controllers;

use App\Http\Requests\PrestationRequest;
use App\Models\Prestation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrestationController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission PrestationController::index
     * @permission_desc Afficher la liste des prestations
     */
    public function index(Request $request)
    {
        return response()->json([
            'prestations' => Prestation::with(['createdBy:id,nom_utilisateur', 'updatedBy:id,nom_utilisateur'])
                ->latest()
                ->paginate(
                    perPage: $request->input('per_page', 25),
                    page: $request->input('page', 1)
                )
        ]);
    }

    /**
     * @param PrestationRequest $request
     * @return JsonResponse
     *
     * @permission PrestationController::store
     * @permission_desc Enregistrer une prestation
     */
    public function store(PrestationRequest $request)
    {
        Prestation::create($request->validated());
        return response()->json([
            'message' => __("Prestation ajoutée avec succès !")
        ]);
    }

    /**
     * @param Prestation $prestation
     * @return Prestation
     */
    public function show(Prestation $prestation)
    {
        return $prestation;
    }

    /**
     * @param PrestationRequest $request
     * @param Prestation $prestation
     * @return JsonResponse
     *
     * @permission PrestationController::update
     * @permission_desc Modifier une prestation
     */
    public function update(PrestationRequest $request, Prestation $prestation)
    {
        $prestation->update($request->validated());

        return response()->json([
            'message' => __("Prestation modifiée avec succès !")
        ]);
    }
}
