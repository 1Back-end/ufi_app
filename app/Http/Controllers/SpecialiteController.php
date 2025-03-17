<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Specialite;
use App\Models\User;
use function Pest\Laravel\json;

class SpecialiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $specialites = Specialite::select('id','nom_specialite')->get();
        return response()->json($specialites);
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation des données d'entrée
        $validated = $request->validate([
            'nom_service_hopi' => 'required|unique:service__hopitals,nom_service_hopi',  // Validation du champ obligatoire et unique
        ]);

        // Récupère l'utilisateur par défaut
        $authUser = User::first();
        if (!$authUser) {
            return response()->json(['message' => 'Aucun utilisateur trouvé'], 404);
        }

        // Création du service hospitalier
        $service_hopital = Service_Hopital::create([
            'nom_service_hopi' => $request->nom_service_hopi,
            'create_by_service_hopi' => $authUser->id
        ]);

        // Retourne la réponse de succès
        return response()->json([
            'message' => 'Service hospitalier créé avec succès',
            'data' => $service_hopital
        ], 201);
        //
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
