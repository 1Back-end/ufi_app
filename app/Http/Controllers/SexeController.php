<?php

namespace App\Http\Controllers;

use App\Http\Requests\SexeRequest;
use App\Models\Sexe;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SexeController extends Controller
{
    public function index()
    {
        return \response()->json([
            'sexes' => Sexe::with(['createBySex:id,nom_utilisateur', 'updateBySex:id,nom_utilisateur'])->get()
        ]);
    }

    public function store(SexeRequest $request)
    {
//        $auth = auth()->user();
        $auth = User::first();
        Sexe::create([
            'description_sex' => $request->description_sex,
            'create_by_sex' => $auth->id,
            'update_by_sex' => $auth->id
        ]);

        return response()->json(['message' => 'Sexe créé avec succès !'], Response::HTTP_CREATED);
    }

    public function update(SexeRequest $request, Sexe $sex)
    {
        $auth = User::first();
        $data = array_merge($request->all(), ['update_by_sex' => $auth->id]);

        $sex->update($data);

        return response()->json(['message' => 'Sexe mis à jour avec succès !'], Response::HTTP_ACCEPTED);
    }

    public function destroy(Sexe $sex)
    {
        if ($sex->clients()->count() > 0) {
            return response()->json([
                'message' => 'Ce sexe ne peut être supprimé car il est utilisé par un client'
            ], Response::HTTP_CONFLICT);
        }

        $sex->delete();

        return response()->json(['message' => 'Sexe supprimé avec succès !'], Response::HTTP_ACCEPTED);
    }
}
