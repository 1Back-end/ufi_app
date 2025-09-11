<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaillasseRequest;
use App\Models\Paillasse;


/**
 * @permission_category Gestion des paillasses
 */
class PaillasseController extends Controller
{
    /**
     * Get all paillasse.
     *
     * @return \Illuminate\Http\Response
     *
     * @permission PaillasseController::index
     * @permission_desc Afficher la liste des paillasses
     */
    public function index()
    {
        return response()->json(Paillasse::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\PaillasseRequest  $request
     * @return \Illuminate\Http\Response
     *
     * @permission PaillasseController::store
     * @permission_desc Ajouter une paillassse
     */
    public function store(PaillasseRequest $request)
    {
        Paillasse::create($request->validated());

        return response()->json([
            'message' => 'Paillasse created successfully'
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\PaillasseRequest  $request
     * @param  \App\Models\Paillasse  $paillasse
     * @return \Illuminate\Http\Response
     *
     * @permission PaillasseController::update
     * @permission_desc Modifier une paillassse
     */
    public function update(PaillasseRequest $request, Paillasse $paillasse)
    {
        $paillasse->update($request->validated());

        return response()->json([
            'message' => 'Paillasse updated successfully'
        ], 202);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Paillasse  $paillasse
     * @return \Illuminate\Http\Response
     *
     * @permission PaillasseController::destroy
     * @permission_desc Supprimer une paillassse
     */
    public function destroy(Paillasse $paillasse)
    {
        $paillasse->delete();

        return response()->json();
    }
}
