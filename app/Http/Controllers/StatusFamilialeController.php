<?php

namespace App\Http\Controllers;

use App\Http\Requests\StatusFamilialeRequest;
use App\Models\Sexe;
use App\Models\StatusFamiliale;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class StatusFamilialeController extends Controller
{
    public function index()
    {
        return \response()->json([
            'status_familiales' => StatusFamiliale::with(['createByStatusfam:id,nom_utilisateur', 'updateByStatusfam:id,nom_utilisateur', 'sexes:id,description_sex'])->get(),
            'sexes' => Sexe::select(['id', 'description_sex'])->get(),
        ]);
    }

    public function store(StatusFamilialeRequest $request)
    {
        $auth = User::first();
//        $auth = auth()->user();
        $statFam = StatusFamiliale::create([
            'description_statusfam' => $request->description_statusfam,
            'create_by_statusfam' => $auth->id,
            'update_by_statusfam' => $auth->id
        ]);

        if ($request->sexes) {
            $statFam->sexes()->sync($request->sexes);
        }

        return \response()->json([
            'message' => 'Status familial created successfully'
        ], Response::HTTP_CREATED);
    }

    public function update(StatusFamilialeRequest $request, StatusFamiliale $status_familiale)
    {
//        $auth = auth()->user();
        $auth = User::first();
        $data = array_merge($request->all(), ['update_by_statusfam' => $auth->id]);

        $status_familiale->update($data);

        if ($request->sexes) {
            $status_familiale->sexes()->sync($request->sexes);
        }

        return \response()->json([
            'message' => 'Status familial updated successfully'
        ], Response::HTTP_ACCEPTED);
    }

    public function destroy(StatusFamiliale $status_familiale)
    {
        if ($status_familiale->clients()->count() > 0) {
            return \response()->json([
                'message' => 'Status familial ne peut être supprimé car il est utilisé par un client'
            ], Response::HTTP_CONFLICT);
        }

        $status_familiale->delete();

        return \response()->json([
            'message' => 'Status familial deleted successfully'
        ], Response::HTTP_ACCEPTED);
    }
}
