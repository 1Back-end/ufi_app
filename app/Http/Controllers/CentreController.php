<?php

namespace App\Http\Controllers;

use App\Http\Requests\CentreRequest;
use App\Models\Centre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CentreController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission CentreController::index
     * @permission_desc Afficher tous les centres
     */
    public function index(Request $request): JsonResponse
    {
        $centres = Centre::with(['createdBy:id,nom_utilisateur', 'updatedBy:id,nom_utilisateur'])
            ->withCount([
                'users',
                'users as clients' => function (Builder $builder) {
                    $builder->has('client');
                }
            ])
            ->paginate(
                perPage: $request->input('per_page'),
                page: $request->input('page')
            );

        return response()->json([
            'centres' => $centres
        ]);
    }

    /**
     * @param CentreRequest $request
     * @return JsonResponse
     * @throws \Throwable
     *
     * @permission CentreController::store
     * @permission_desc Enregistrer un client
     */
    public function store(CentreRequest $request): JsonResponse
    {
        $ref = Str::random(32);

        DB::beginTransaction();
        try {
            $centre = Centre::create(array_merge($request->validated(), ['reference' => $ref]));

            // Save Logo
            if ($request->hasFile('logo')) {
                upload_media(
                    model: $centre,
                    file: $request->file('logo'),
                    name: 'logo',
                    disk: 'public',
                    path: 'logo/centre'
                );
            }

        } catch (\Exception $e) {
            DB::rollBack();

            // Supprimer le logo si celui-ci avait été enregistrer
            if ($request->hasFile('logo')) {
                delete_media(
                    disk: 'public',
                    path: 'logo/centre' .'/'. $request->file('logo')->getClientOriginalName(),
                );
            }

            Log::error('Error create Centre:  ' . $e->getMessage());

            return response()->json([
                'message' => __('Une erreur est survenue lors de la création d\'un centre !')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        DB::commit();

        return response()->json([
            'message' => __("Centre créer avec success !")
        ], Response::HTTP_CREATED);
    }

    /**
     * @param Centre $centre
     * @return JsonResponse
     *
     * @permission CentreController::index
     * @permission_desc Afficher tous les centres
     */
    public function show(Centre $centre): JsonResponse
    {

    }

    /**
     * @param CentreRequest $request
     * @param Centre $centre
     * @return JsonResponse
     */
    public function update(CentreRequest $request, Centre $centre): JsonResponse
    {
    }

    /**
     * @param Centre $centre
     * @return JsonResponse
     */
    public function destroy(Centre $centre): JsonResponse
    {
    }
}
