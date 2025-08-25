<?php

namespace App\Http\Controllers;

use App\Models\RapportActe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RapportActeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * @return JsonResponse
     *
     * @permission RapportActeController::store
     * @permission_desc Ajouter des actes au rapport de consultation d'un client
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $request->validate([
            'rapport_consultation_id' => 'nullable|exists:ops_tbl_rapport_consultations,id',
            'type' => 'required|string|in:Oui,Non',
            'acte_id' => 'nullable|array',
            'acte_id.*' => 'exists:actes,id',
            'actes_libres' => 'nullable|array',
            'actes_libres.*.name' => 'required_if:type,Non|string',
            'actes_libres.*.description' => 'nullable|string',
        ]);

        if ($request->type === 'Oui' && $request->acte_id) {
            foreach ($request->acte_id as $acteId) {
                RapportActe::create([
                    'rapport_consultation_id' => $request->rapport_consultation_id,
                    'acte_id' => $acteId,
                    'type' => 'Oui',
                    'created_by' => $auth->id,
                    'updated_by' => $auth->id,
                ]);
            }
        }

        if ($request->type === 'Non' && $request->actes_libres) {
            foreach ($request->actes_libres as $acteLibre) {
                RapportActe::create([
                    'rapport_consultation_id' => $request->rapport_consultation_id,
                    'name' => $acteLibre['name'],
                    'description' => $acteLibre['description'] ?? null,
                    'type' => 'Non',
                    'created_by' => $auth->id,
                    'updated_by' => $auth->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Rapports actes enregistrés avec succès',
            'success' => true,
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
