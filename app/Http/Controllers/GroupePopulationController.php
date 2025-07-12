<?php


namespace App\Http\Controllers;

use App\Http\Requests\GroupePopulationRequest;
use App\Models\GroupePopulation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class GroupePopulationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     *
     * @permission GroupePopulationController::index
     * @permission_desc Afficher la liste des groupes de population.
     */
    public function index()
    {
        return response()->json(GroupePopulation::all());
    }

    /**
     * Store a newly created GroupePopulation resource in storage.
     *
     * @param GroupePopulationRequest $request
     * @return JsonResponse
     *
     * @permission GroupePopulationController::store
     * @permission_desc Créer un nouveau groupe de population.
     */
    public function store(GroupePopulationRequest $request)
    {
        $data = array_merge($request->validated(), [
            'code' => str()->slug($request->name),
        ]);


        GroupePopulation::create($data);
        return response()->json([
            'message' => 'Groupe population created'
        ], 201);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param GroupePopulationRequest $request
     * @param GroupePopulation $groupePopulation
     * @return JsonResponse
     *
     * @permission GroupePopulationController::update
     * @permission_desc Mettre jour un groupe de population.
     */
    public function update(GroupePopulationRequest $request, GroupePopulation $groupePopulation)
    {
        $data = array_merge($request->validated(), [
            'code' => str()->slug($request->name),
        ]);

        $groupePopulation->update($data);

        return \response()->json([
            'message' => 'Groupe population updated'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param GroupePopulation $groupePopulation
     * @return JsonResponse
     *
     * @permission GroupePopulationController::destroy
     * @permission_desc Supprimer un groupe de population.
     */
    public function destroy(GroupePopulation $groupePopulation)
    {
        $groupePopulation->delete();

        return response()->json();
    }
}
