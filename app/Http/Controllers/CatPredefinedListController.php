<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatPredefinedListRequest;
use App\Models\CatPredefinedList;
use App\Models\PredefinedList;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CatPredefinedListController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @permission CatPredefinedListController::index
     * @permission_desc Afficher la liste des catégories des listes prédéfinies
     */
    public function index()
    {
        return response()->json(
            CatPredefinedList::with('predefinedLists')
                ->when(request('search'), function ($query) {
                    return $query->where('name', 'like', '%' . request('search') . '%');
                })
                ->paginate(
                    perPage: request('per_page', 25),
                    page: request('page', 1)
                )
        );
    }

    /**
     * @return JsonResponse
     *
     * @permission CatPredefinedListController::predefinedLists
     * @permission_desc Afficher la liste des listes prédéfinies
     */
    public function predefinedLists()
    {
        return response()->json(
            PredefinedList::with(['catPredefinedList'])
                ->when(request('cat_predefined_list_id'), function ($query) {
                    return $query->where('cat_predefined_list_id', request('cat_predefined_list_id'));
                })
                ->get()
        );
    }

    /**
     * @param CatPredefinedListRequest $request
     * @return JsonResponse
     *
     * @permission CatPredefinedListController::store
     * @permission_desc Créer une nouvelle catégorie de listes prédéfinies
     * @throws \Throwable
     */
    public function store(CatPredefinedListRequest $request)
    {
        DB::beginTransaction();
        try {
            $cat = CatPredefinedList::create([
                ...$request->validated(),
                'slug' => str()->slug($request->name),
            ]);

            if (request('predefined_lists')) {
                foreach ($request->input('predefined_lists') as $predefined_list) {
                    PredefinedList::create([
                        'name' => $predefined_list['name'],
                        'show' => $predefined_list['show'],
                        'slug' => str()->slug($predefined_list['name']),
                        'cat_predefined_list_id' => $cat->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'message' => "Une erreur s'est produite lors de la création de la catégorie de listes prédéfinies"
            ], 400);
        }
        DB::commit();

        return response()->json([
            'message' => 'Predefined list created successfully'
        ], 201);
    }

    /**
     * @param CatPredefinedListRequest $request
     * @param CatPredefinedList $catPredefinedList
     * @return JsonResponse
     *
     * @permission CatPredefinedListController::update
     * @permission_desc Modifier une catégorie de listes prédéfinies
     * @throws \Throwable
     */
    public function update(CatPredefinedListRequest $request, CatPredefinedList $catPredefinedList)
    {
        DB::beginTransaction();
        try {
            $catPredefinedList->update($request->validated());

            $ids = Arr::pluck($request->input('predefined_lists'), 'id');
            $catPredefinedList->predefinedLists()->whereNotIn('predefined_lists.id', $ids)->delete();
            if (request('predefined_lists')) {
                foreach ($request->input('predefined_lists') as $predefined_list) {
                    PredefinedList::updateOrCreate([
                        'id' => str()->slug($predefined_list['id']),
                    ], [
                        'name' => $predefined_list['name'],
                        'slug' => str()->slug($predefined_list['name']),
                        'cat_predefined_list_id' => $catPredefinedList->id,
                        'show' => $predefined_list['show'],
                    ]);
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'message' => "Une erreur s'est produite lors de la modification de la catégorie de listes prédéfinies"
            ], 400);
        }
        DB::commit();

        return response()->json([
            'message' => 'Predefined list updated successfully'
        ], 202);
    }

    /**
     * @param CatPredefinedList $catPredefinedList
     * @return JsonResponse
     *
     * @permission CatPredefinedListController::destroy
     * @permission_desc Supprimer une catégorie de listes prédéfinies
     */
    public function destroy(CatPredefinedList $catPredefinedList)
    {
        $catPredefinedList->delete();

        return response()->json();
    }
}
