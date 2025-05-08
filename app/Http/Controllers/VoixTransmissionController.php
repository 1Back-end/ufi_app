<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\VoixTransmissions;
use App\Models\User;

class VoixTransmissionController extends Controller
{
    public function listIdName()
    {
        $voies = VoixTransmissions::select('id', 'name')
            ->where('is_deleted', false)
            ->get();

        return response()->json([
            'voies_transmissions' => $voies
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission VoixTransmissionController::index
     * @permission_desc Afficher la liste des voix d'administrations des produits
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 5);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante
        $search = $request->input('search');
        $query = VoixTransmissions::where('is_deleted', false);
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            });
        }

// Récupérer les assureurs avec pagination
        $voix_tranmissions = VoixTransmissions::where('is_deleted', false)
            ->with([
                'creator:id,nom_utilisateur',
                'updater:id,nom_utilisateur',
            ])
            ->paginate($perPage);

        return response()->json([
            'data' => $voix_tranmissions->items(),
            'current_page' => $voix_tranmissions->currentPage(),  // Page courante
            'last_page' => $voix_tranmissions->lastPage(),  // Dernière page
            'total' => $voix_tranmissions->total(),  // Nombre total d'éléments
        ]);
        //
    }

    /**
     * Display a listing of the resource.
     * @permission VoixTransmissionController::store
     * @permission_desc Enregistrer une voix d'administration de produits
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        try {
            $data = $request->validate([
                'name' => 'required|string|unique:voix_transmissions,name',
                'code'=> 'required|string'
            ]);

            $data['created_by'] = $auth->id;

            $voix = VoixTransmissions::create($data);

            return response()->json([
                'data' => $voix,
                'message' => 'Enregistrement effectué avec succès'
            ]);

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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Display a listing of the resource.
     * @permission VoixTransmissionController::update
     * @permission_desc Modifier une voix de d'administration produits
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        try {
            // Récupérer l'élément à mettre à jour par son ID
            $voix_transmission = VoixTransmissions::where('is_deleted',false)->first();  // Récupère l'élément ou renvoie une erreur 404

            // Validation des données d'entrée
            $data = $request->validate([
                'name' => 'required|string|unique:voix_transmissions,name,' . $voix_transmission->id,  // Vérifie que le nom est unique sauf pour l'élément actue// Description obligatoire
                'code'=>'required|string|unique:voix_transmissions,code,' . $voix_transmission->id,
            ]);

            // Mise à jour des données
            $data['updated_by'] = $auth->id;  // Ajouter l'ID de l'utilisateur qui met à jour

            // Appliquer la mise à jour
            $voix_transmission->update($data);

            // Retourner une réponse JSON avec les données mises à jour et un message de succès
            return response()->json([
                'data' => $voix_transmission,
                'message' => 'Mise à jour effectuée avec succès'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Erreur de validation
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Erreur générale
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     * @permission VoixTransmissionController::show
     * @permission_desc Afficher les détails d'une voix d'adminsitration des produits
     */
    public function show($id)
    {
        try {
            // Récupérer l'élément par ID ou retourner une erreur 404 si non trouvé
            $voix_transmission = VoixTransmissions::where('id', $id)
                ->where('is_deleted', false)  // Assurez-vous de vérifier si l'élément n'est pas marqué comme supprimé
                ->firstOrFail();

            // Retourner une réponse JSON avec les détails de la ressource
            return response()->json([
                'data' => $voix_transmission,
                'message' => 'Détails récupérés avec succès'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si l'élément n'est pas trouvé, retourner une erreur 404
            return response()->json([
                'error' => 'Ressource non trouvée',
                'message' => 'Aucune ressource trouvée avec cet ID.'
            ], 404);
        } catch (\Exception $e) {
            // Autres erreurs
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Display a listing of the resource.
     * @permission VoixTransmissionController::delete
     * @permission_desc Supprimer une voix d'administrations des produits
     */
    public function destroy(string $id)
    {
        $voixTransmission = VoixTransmissions::findOrFail($id);

        // Vérifie s'il est utilisé dans un produit
        $isUsed = Product::where('voix_transmissions_id', $id)->exists();

        if ($isUsed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de supprimer : cette voie de transmission est utilisée par au moins un produit.'
            ], 400);
        }

        $voixTransmission->is_deleted = true;
        $voixTransmission->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Voie de transmission supprimée avec succès.'
        ]);
    }

}
