<?php

namespace App\Http\Controllers;

use App\Http\Requests\CentreRequest;
use App\Models\Centre;
use App\Models\Media;
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
                    path: 'logo/centre',
                    filename: "$ref-logo-" . now()->timestamp
                );
            }

        } catch (\Exception $e) {
            DB::rollBack();

            // Supprimer le logo si celui-ci avait été enregistrer
            if ($request->hasFile('logo')) {
                delete_media(
                    disk: 'public',
                    path: 'logo/centre' . '/' . $request->file('logo')->getClientOriginalName(),
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
     * @permission CentreController::show
     * @permission_desc Afficher un centre
     */
    public function show(Centre $centre): JsonResponse
    {
        return response()->json([
            'centre' => $centre->load(['createdBy:id,nom_utilisateur', 'updatedBy:id,nom_utilisateur']),
        ]);
    }

    /**
     * @param CentreRequest $request
     * @param Centre $centre
     * @return JsonResponse
     * @throws \Throwable
     *
     * @permission CentreController::update
     * @permission_desc MIse à jour d’un centre
     */
    public function update(CentreRequest $request, Centre $centre): JsonResponse
    {
        DB::beginTransaction();
        try {
            $centre->update($request->validated());

            // Save Logo
            if ($request->hasFile('logo')) {
                upload_media(
                    model: $centre,
                    file: $request->file('logo'),
                    name: 'logo',
                    disk: 'public',
                    path: 'logo/centre',
                    filename: "{$centre->reference}-logo-" . now()->timestamp,
                    update: $centre->medias()->where('name', 'logo')->first()
                );
            }
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error create Centre:  ' . $e->getMessage());

            return response()->json([
                'message' => __('Une erreur est survenue lors de la création d\'un centre !')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        DB::commit();

        return \response()->json([
            'message' => __("Mise à jour effectuée avec succès !")
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * @param Centre $centre
     * @return JsonResponse
     *
     * @permission CentreController::destroy
     * @permission_desc Suppression d’un centre
     */
    public function destroy(Centre $centre): JsonResponse
    {
        $centre->medias->each(function (Media $media) {
            delete_media(
                $media->disk,
                $media->path . '/' . $media->filename,
                $media
            );
        });

        $centre->delete();

        return \response()->json([
            'message' => __("Centre supprimé avec succès !")
        ], Response::HTTP_ACCEPTED);
    }
}
