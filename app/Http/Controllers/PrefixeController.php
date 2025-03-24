<?php

namespace App\Http\Controllers;

use App\Http\Requests\PrefixeRequest;
use App\Models\Prefix;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class PrefixeController extends Controller
{
    public function index()
    {
        return response()->json([
            'prefixes' => Prefix::with(['createBy:id,nom_utilisateur', 'updateBy:id,nom_utilisateur'])->get()
        ]);
    }

    public function store(PrefixeRequest $request)
    {
        $auth = User::first();
//        $auth = auth()->user();
        Prefix::create([
            'prefixe' => $request->prefixe,
            'position' => $request->position,
            'age_min' => $request->age_min,
            'age_max' => $request->age_max,
            'create_by' => $auth->id,
            'update_by' => $auth->id
        ]);

        return response()->json([
            'message' => 'Prefixe created successfully'
        ], Response::HTTP_CREATED);
    }

    public function update(PrefixeRequest $request, Prefix $prefix)
    {
        $auth = User::first();
//        $auth = auth()->user();

        $data = array_merge($request->all(), ['update_by' => $auth->id]);

        $prefix->update($data);

        return response()->json([
            'message' => 'Prefixe updated successfully'
        ], Response::HTTP_ACCEPTED);
    }

    public function destroy(Prefix $prefix)
    {
        if ($prefix->clients()->count() > 0) {
            return response()->json([
                'message' => 'Prefixe ne peutêtre supprimé car il est utilisé par un client'
            ], Response::HTTP_CONFLICT);
        }

        $prefix->delete();
        return response()->json([
            'message' => 'Prefixe deleted successfully'
        ], Response::HTTP_ACCEPTED);
    }
}
