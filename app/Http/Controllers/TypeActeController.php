<?php

namespace App\Http\Controllers;

use App\Http\Requests\TypeActeRequest;
use App\Models\TypeActe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TypeActeController extends Controller
{
    /**
     * Affiche la liste des types d'actes.
     *
     * @return JsonResponse
     *
     * @permission TypeActeController::index
     * @permission_desc Afficher la liste des types d'actes
     */
    public function index()
    {
        return response()->json([
            'type_actes' => TypeActe::with(['createdBy:id,nom_utilisateur', 'updatedBy:id,nom_utilisateur'])->get(),
        ]);
    }

    /**
     * Enregistrer un type d'acte.
     *
     * @return JsonResponse
     *
     * @permission TypeActeController::store
     * @permission_desc Enregistrer un type d'acte
     */
    public function store(TypeActeRequest $request)
    {
        TypeActe::create($request->validated());

        return response()->json([
            'message' => 'Type acte created successfully'
        ], 201);
    }

    /**
     * Mise à jour d'un type d'acte.
     *
     * @return JsonResponse
     *
     * @permission TypeActeController::update
     * @permission_desc Mise à jour d'un type d'acte
     */
    public function update(TypeActeRequest $request, TypeActe $typeActe)
    {
        $typeActe->update($request->validated());

        return response()->json([
            'message' => 'Operation successful'
        ], 202);
    }

    /**
     * Changer le status d'un type d'acte.
     *
     * @return JsonResponse
     *
     * @permission TypeActeController::changeStatus
     * @permission_desc Changer le status d'un type d'acte
     */
    public function changeStatus(TypeActe $typeActe, Request $request)
    {
        $request->validate([
            'state' => ['required', 'in:0,1'],
        ]);

        $typeActe->state = $request->state;
        $typeActe->save();

        return response()->json([
            'message' => 'Operation successful'
        ], 202);
    }
}
