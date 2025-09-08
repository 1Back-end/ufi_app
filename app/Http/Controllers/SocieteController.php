<?php

namespace App\Http\Controllers;

use App\Http\Requests\SocieteRequest;
use App\Models\Societe;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SocieteController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @permission SocieteController::index
     * @permission_desc Afficher la liste des sociétés
     */
    public function index()
    {
        return response()->json([
            'societes' => Societe::with(['createBy:id,nom_utilisateur', 'updatedBy:id,nom_utilisateur'])
                ->when(\request()->input('search'), function ($query) {
                    $query->where('nom_soc_cli', 'like', '%' . request()->input('search') . '%');
                })
                ->orderBy('created_at', 'desc')
                ->paginate(
                    perPage: request()->input('per_page', 5),
                    page: request()->input('page', 1)
                )
        ]);
    }

    /**
     * @param SocieteRequest $request
     * @return JsonResponse
     *
     * @permission SocieteController::store
     * @permission_desc Créer une société
     */
    public function store(SocieteRequest $request)
    {
//        $auth = auth()->user();
        // $auth = User::first();
        $auth = auth()->user();
        $societe = Societe::create([
            'nom_soc_cli' => $request->nom_soc_cli,
            'tel_soc_cli' => $request->tel_soc_cli,
            'Adress_soc_cli' => $request->Adress_soc_cli,
            'num_contrib_soc_cli' => $request->num_contrib_soc_cli,
            'email_soc_cli' => $request->email_soc_cli,
            'create_by' => $auth->id,
            'updated_by' => $auth->id,
        ]);

        return response()->json([
            'message' => 'Societe created successfully',
            'societe' => $societe
        ], Response::HTTP_CREATED);
    }

    /**
     * @param SocieteRequest $request
     * @param Societe $societe
     * @return JsonResponse
     *
     * @permission SocieteController::update
     * @permission_desc Mise à jour d’une société
     */
    public function update(SocieteRequest $request, Societe $societe)
    {
        // $auth = User::first();
        $auth = auth()->user();

        $data = array_merge($request->all(), ['update_by' => $auth->id]);

        $societe->update($data);

        return response()->json([
            'message' => 'Societe updated successfully'
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * @param Societe $societe
     * @return JsonResponse
     *
     * @permission SocieteController::destroy
     * @permission_desc Supprimé une société.
     */
    public function destroy(Societe $societe)
    {
        if ($societe->clients()->count() > 0) {
            return response()->json([
                'message' => 'Societe ne peut-être supprimé car il est utilisé par un client'
            ], Response::HTTP_CONFLICT);
        }

        $societe->delete();

        return response()->json([
            'message' => 'Societe deleted successfully'
        ], Response::HTTP_ACCEPTED);
    }
}
