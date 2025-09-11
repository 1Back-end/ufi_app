<?php

namespace App\Http\Controllers;

use App\Http\Requests\SexeRequest;
use App\Models\Prefix;
use App\Models\Sexe;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * @permission_category Gestion des sexes
 */

class SexeController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @permission SexeController::index
     * @permission_desc Afficher la liste des sexes
     */
    public function index()
    {
        return \response()->json([
            'prefixes' => Prefix::select(['id', 'prefixe'])->get(),
            'sexes' => Sexe::with(['createBySex:id,nom_utilisateur', 'updateBySex:id,nom_utilisateur', 'prefixes:id,prefixe,position', 'status_families:id,description_statusfam'])->get()
        ]);
    }

    /**
     * @param SexeRequest $request
     * @return JsonResponse
     *
     * @permission SexeController::store
     * @permission_desc Enregistrer un sexe
     */
    public function store(SexeRequest $request)
    {
//        $auth = auth()->user();
        $auth = User::first();
        $sex = Sexe::create([
            'description_sex' => $request->description_sex,
            'create_by_sex' => $auth->id,
            'update_by_sex' => $auth->id
        ]);

        if ($request->input('prefixes')) {
            $sex->prefixes()->sync($request->input('prefixes'));
        }
        return response()->json(['message' => 'Sexe créé avec succès !'], Response::HTTP_CREATED);
    }

    /**
     * @param SexeRequest $request
     * @param Sexe $sex
     * @return JsonResponse
     *
     * @permission SexeController::update
     * @permission_desc Mise à jour d’un sexe
     */
    public function update(SexeRequest $request, Sexe $sex)
    {
        $auth = User::first();
        $data = array_merge($request->all(), ['update_by_sex' => $auth->id]);

        $sex->update($data);

        if ($request->input('prefixes')) {
            $sex->prefixes()->sync($request->input('prefixes'));
        }

        return response()->json(['message' => 'Sexe mis à jour avec succès !'], Response::HTTP_ACCEPTED);
    }

    /**
     * @param Sexe $sex
     * @return JsonResponse
     *
     * @permission SexeController::destroy
     * @permission_desc Supprimer un sexe
     */
    public function destroy(Sexe $sex)
    {
        if ($sex->clients()->count() > 0) {
            return response()->json([
                'message' => 'Ce sexe ne peut être supprimé car il est utilisé par un client'
            ], Response::HTTP_CONFLICT);
        }

        $sex->delete();

        return response()->json(['message' => 'Sexe supprimé avec succès !'], Response::HTTP_ACCEPTED);
    }
}
