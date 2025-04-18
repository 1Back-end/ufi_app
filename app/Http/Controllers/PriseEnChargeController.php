<?php

namespace App\Http\Controllers;
use App\Models\Client;
use App\Models\PriseEnCharge;

use Illuminate\Http\Request;

class PriseEnChargeController extends Controller
{

    public function getAllClients()
    {
        $clients = Client::select('id', 'nomcomplet_client')
            ->get();

        return response()->json([
            'clients' => $clients
        ]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 10);
        $page = $request->input('page', 1);

        $prise_en_charges = PriseEnCharge::where('is_deleted', false)
            ->with([
                'assureur:id,nom',
                'quotation:id,code',
                'client:id,nomcomplet_client',
                'creator:id,login',
                'updater:id,login'
            ])
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $prise_en_charges->items(),
            'current_page' => $prise_en_charges->currentPage(),
            'last_page' => $prise_en_charges->lastPage(),
            'total' => $prise_en_charges->total(),
        ]);//
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        try {
            $data = $request->validate([
                'assureurs_id' => 'required|exists:assureurs,id',
                'quotations_id' => 'required|exists:quotations,id',
                'date' => 'required|date',
                'date_debut' => 'required|date',
                'date_fin' => 'required|date',
                'clients_id' => 'required|exists:clients,id',
                'taux_pc' => 'required|numeric',
            ]);

            $data['created_by'] = $auth->id;

            $prise_en_charge = PriseEnCharge::create($data);

            return response()->json([
                'data' => $prise_en_charge,
                'message' => 'Prise en charge enregistrée avec succès'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $prise_en_charge = PriseEnCharge::where('is_deleted', false)->findOrFail($id);
            return response()->json($prise_en_charge);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Prise en charge introuvable'], 404);
        }
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
        try {
            $auth = auth()->user();

            $data = $request->validate([
                'assureurs_id' => 'required|exists:assureurs,id',
                'quotations_id' => 'required|exists:quotations,id',
                'date' => 'required|date',
                'date_debut' => 'required|date',
                'date_fin' => 'required|date',
                'clients_id' => 'required|exists:clients,id',
                'taux_pc' => 'required|numeric',
            ]);

            $prise_en_charge = PriseEnCharge::where('is_deleted', false)->findOrFail($id);

            $data['updated_by'] = $auth->id;

            $prise_en_charge->update($data);

            return response()->json([
                'data' => $prise_en_charge,
                'message' => 'Prise en charge mise à jour avec succès'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Prise en charge non trouvée'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $prise_en_charge = PriseEnCharge::where('is_deleted', false)->findOrFail($id);
            $prise_en_charge->update([
                'is_deleted' => true,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Prise en charge supprimée avec succès'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Prise en charge non trouvée'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
