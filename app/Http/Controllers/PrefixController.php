<?php

namespace App\Http\Controllers;

use App\Http\Requests\PrefixeRequest;
use App\Models\Prefix;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PrefixController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @permission PrefixController::index
     * @permission_desc Afficher la liste des préfixes
     */
    public function index()
    {
        return response()->json([
            'prefixes' => Prefix::with(['createBy:id,nom_utilisateur', 'updateBy:id,nom_utilisateur'])->get()
        ]);
    }

    /**
     * @param PrefixeRequest $request
     * @return JsonResponse
     *
     * @permission PrefixController::store
     * @permission_desc Enrégistrer un préfixe
     */
    public function store(PrefixeRequest $request)
    {
        Prefix::create([
            'prefixe' => $request->prefixe,
            'position' => $request->position,
            'age_min' => $request->age_min,
            'age_max' => $request->age_max,
        ]);

        return response()->json([
            'message' => 'Préfixe created successfully'
        ], Response::HTTP_CREATED);
    }

    /**
     * @param PrefixeRequest $request
     * @param Prefix $prefix
     * @return JsonResponse
     *
     * @permission PrefixController::update
     * @permission_desc Mise à jour d’un préfixe
     */
    public function update(PrefixeRequest $request, Prefix $prefix)
    {
        $prefix->update($request->all());

        return response()->json([
            'message' => 'Préfixe updated successfully'
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * @param Prefix $prefix
     * @return JsonResponse
     *
     * @permission PrefixController::destroy
     * @permission_desc Supprimer un préfixe
     */
    public function destroy(Prefix $prefix)
    {
        if ($prefix->clients()->count() > 0) {
            return response()->json([
                'message' => 'Préfixe ne peut-être supprimé car il est utilisé par un client'
            ], Response::HTTP_CONFLICT);
        }

        $prefix->delete();
        return response()->json([
            'message' => 'Préfixe deleted successfully'
        ], Response::HTTP_ACCEPTED);
    }
}
