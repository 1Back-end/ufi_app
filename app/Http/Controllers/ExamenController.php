<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExamenRequest;
use App\Models\Examen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class ExamenController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     *
     * @permission ExamenController::index
     * @permission_desc Afficher la liste des examens
     */
    public function index()
    {
        return response()->json(
            Examen::with(['paillasse', 'subFamilyExam', 'typePrelevement', 'tubePrelevement', 'kbPrelevement'])
                ->when(request('search'), function ($query) {
                    $query->where('name', 'like', '%' . request('search') . '%')
                        ->orWhere('name1', 'like', '%' . request('search') . '%')
                        ->orWhere('name2', 'like', '%' . request('search') . '%')
                        ->orWhere('name3', 'like', '%' . request('search') . '%')
                        ->orWhere('name4', 'like', '%' . request('search') . '%');
                })
                ->paginate(
                    perPage: request('per_page', 25),
                    page: request('page', 1)
                )
        );
    }

    /**
     * Store a newly created examen in storage.
     *
     * @param ExamenRequest $request
     * @return JsonResponse
     *
     * @permission ExamenController::store
     * @permission_desc Ajout d’un examen
     */
    public function store(ExamenRequest $request)
    {
        Examen::create($request->validated());

        return response()->json([
            'message' => 'Examen created successfully'
        ], ResponseAlias::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  Examen  $examen
     * @return Examen
     *
     * @permission ExamenController::show
     * @permission_desc Afficher un examen
     */
    public function show(Examen $examen)
    {
        return $examen->load(['paillasse', 'subFamilyExam', 'typePrelevement', 'tubePrelevement', 'kbPrelevement']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ExamenRequest $request
     * @param Examen $examen
     * @return JsonResponse
     *
     * @permission ExamenController::update
     * @permission_desc Mettre a jour un examen
     */
    public function update(ExamenRequest $request, Examen $examen)
    {
        $examen->update($request->validated());

        return response()->json([
            'message' => 'Examen updated successfully'
        ], ResponseAlias::HTTP_OK);
    }

    /**
     * Remove the specified examen from storage.
     *
     * @param Examen $examen
     * @return JsonResponse
     *
     * @permission ExamenController::destroy
     * @permission_desc Supprimer un examen
     */
    public function destroy(Examen $examen)
    {
        $examen->delete();

        return response()->json();
    }
}
