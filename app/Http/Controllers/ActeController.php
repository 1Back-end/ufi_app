<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActeRequest;
use App\Models\Acte;
use App\Models\TypeActe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActeController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission ActeController::index
     * @permission_desc Afficher la liste des actes
     */
    public function index()
    {
        return response()->json([
            'type_actes' => TypeActe::with(['actes', 'actes.createdBy:id,nom_utilisateur', 'actes.updatedBy:id,nom_utilisateur'])->get()
        ]);
    }

    /**
     * @param ActeRequest $request
     * @return JsonResponse
     *
     * @permission ActeController::store
     * @permission_desc Enregistrer un acte
     */
    public function store(ActeRequest $request)
    {
        Acte::create($request->validated());
        return response()->json([
            'message' => __("Acte crée avec succès !")
        ],  201);
    }

    /**
     * @param ActeRequest $request
     * @param Acte $acte
     * @return JsonResponse
     *
     * @permission ActeController::update
     * @permission_desc Mettre à jour un acte
     */
    public function update(ActeRequest $request, Acte $acte)
    {
        $acte->update($request->validated());

        return response()->json([
            'message' => __('Mise à jour effectuée avec succès !')
        ], 202);
    }

    /**
     * @param Acte $acte
     * @param Request $request
     * @return JsonResponse
     *
     * @permission ActeController::changeStatus
     * @permission_desc Changer le status d'un acte
     */
    public function changeStatus(Acte $acte, Request $request)
    {
        $request->validate([
            "state" => ['required', 'boolean']
        ]);

        $acte->update(['state' => $request->input('state')]);

        return response()->json([
            'message' => __("Status mis à jour")
        ],202);
    }
}
