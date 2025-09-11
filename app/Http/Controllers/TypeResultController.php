<?php

namespace App\Http\Controllers;

use App\Enums\InputType;
use App\Http\Requests\TypeResultRequest;
use App\Models\CatPredefinedList;
use App\Models\TypeResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @permission_category Gestion des types de résultat.
 */


class TypeResultController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @permission TypeResultController::index
     * @permission_desc Afficher la liste des types de résultat.
     */
    public function index()
    {
        return response()->json([
            'types' => TypeResult::when(request('search'), function (Builder $query) {
                $query->where('name', 'like', '%' . request('search') . '%');
            })->paginate(
                perPage: request('per_page', 25),
                page: request('page', 1),
            ),
            'cat_predefined_lists' => CatPredefinedList::all()
        ]);
    }

    /**
     * @param TypeResultRequest $request
     * @return JsonResponse
     *
     * @permission TypeResultController::store
     * @permission_desc Créer un type de résultat
     */
    public function store(TypeResultRequest $request)
    {
        TypeResult::create($request->validated());

        return response()->json([
            'message' => 'Type de résultat créé',
        ], 201);
    }

    /**
     * @param TypeResultRequest $request
     * @param TypeResult $typeResult
     * @return JsonResponse
     *
     * @permission TypeResultController::update
     * @permission_desc Mettre à jour un type de résultat
     */
    public function update(TypeResultRequest $request, TypeResult $typeResult)
    {
        $data = $request->validated();
        if ($request->input('type') != InputType::SELECT->value && $request->input('type') != InputType::SELECT2->value) {
            $data['cat_predefined_list_id'] = null;
        }

        $typeResult->update($data);

        return response()->json([
            'message' => 'Type de résultat mis à jour',
        ], 202);
    }

    /**
     * @param TypeResult $typeResult
     * @return JsonResponse
     *
     * @permission TypeResultController::destroy
     * @permission_desc Supprimer un type de résultat
     */
    public function destroy(TypeResult $typeResult)
    {
        $typeResult->delete();

        return response()->json();
    }
}
