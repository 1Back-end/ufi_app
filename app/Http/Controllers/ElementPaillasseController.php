<?php

namespace App\Http\Controllers;

use App\Models\ElementPaillasse;
use Illuminate\Http\JsonResponse;


/**
 * @permission_category Gestion des éléments de paillasse d'examen
 */
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
                ->when(request('search'), function ($query) {
                    $query->where(function ($q) {
                        $q->where('name', 'like', '%' . request('search') . '%');
                    });
                })
                ->paginate(
                    perPage: request()->get('per_page', 25),
                    page: request()->get('page', 1),
                )
        );
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
