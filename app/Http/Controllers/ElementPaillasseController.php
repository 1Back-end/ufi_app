<?php

namespace App\Http\Controllers;

use App\Http\Requests\ElementPaillasseRequest;
use App\Models\ElementPaillasse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ElementPaillasseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @permission ElementPaillasseController::index
     * @permission_desc Afficher la liste des éléments de paillasse d'examen
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json(
            ElementPaillasse::with([
                'typeResult',
                'examen',
                'group_populations',
                'createdBy',
                'updatedBy',
            ])
                ->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @permission ElementPaillasseController::store
     * @permission_desc Ajouter un nouvel élément de paillasse d'examen
     *
     * @param ElementPaillasseRequest $request
     * @return JsonResponse
     */
    public function store(ElementPaillasseRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $elementPaillasse = ElementPaillasse::create($request->validated());

            foreach ($request->normal_values as $normal_value) {
                $elementPaillasse->group_populations()->attach($normal_value['populate_id'], [
                    'value' => $normal_value['value'],
                    'value_max' => $normal_value['value_max'],
                    'sign' => $normal_value['sign'],
                ]);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'message' => "Erreur lors de l'ajout de l'élément de paillasse d'examen",
                'error' => $e->getMessage(),
            ], 500);
        }
        DB::commit();

        return response()->json([
            'message' => "Élément de paillasse d'examen ajouté avec succès",
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @permission ElementPaillasseController::update
     * @permission_desc Mettre à jour un élément de paillasse d'examen
     *
     * @param ElementPaillasseRequest $request
     * @param ElementPaillasse $elementPaillasse
     * @return JsonResponse
     */
    public function update(ElementPaillasseRequest $request, int $id): JsonResponse
    {
        $elementPaillasse = ElementPaillasse::findOrFail($id);
        DB::beginTransaction();
        try {
            $elementPaillasse->update($request->validated());

            $elementPaillasse->group_populations()->detach();
            foreach ($request->normal_values as $normal_value) {
                $elementPaillasse->group_populations()->attach($normal_value['populate_id'], [
                    'value' => $normal_value['value'],
                    'value_max' => $normal_value['value_max'],
                    'sign' => $normal_value['sign'],
                ]);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'message' => "Erreur lors de la mise à jour de l'élément de paillasse d'examen",
                'error' => $e->getMessage(),
            ], 500);
        }
        DB::commit();

        return response()->json([
            'message' => "Élément de paillasse d'examen mis à jour avec succès",
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @permission ElementPaillasseController::destroy
     * @permission_desc Supprimer un élément de paillasse d'examen
     *
     * @param ElementPaillasse $elementPaillasse
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $elementPaillasse = ElementPaillasse::findOrFail($id);

        $elementPaillasse->delete();

        return response()->json([
            'message' => "Élément de paillasse d'examen supprimé avec succès",
        ], 204);
    }
}
