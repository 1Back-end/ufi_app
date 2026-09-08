<?php

namespace App\Http\Controllers;

use App\Models\CategorieAntecedent;
use App\Models\OpsTblAntecedent;
use App\Models\RendezVous;
use Illuminate\Http\Request;


/**
 * @permission_category Gestion des antécédants.
 * @permission_module Gestion des prestations
 */
class OpsTblAntecedentController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission OpsTblAntecedentController::index
     * @permission_desc Afficher  la liste des antécédants d'un client
     */
    public function index(Request $request, $client_id)
    {
        $perPage = $request->input('limit', 5);

        $antecedents = OpsTblAntecedent::where('client_id', $client_id)
            ->with([
                'createdBy',
                'updatedBy',
                'client',
                'categorie',
                'sousCategorie'
            ])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%')
                        ->orWhere('id', 'like', '%' . $search . '%');
                });
            })
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => $antecedents->items(),
            'current_page' => $antecedents->currentPage(),
            'last_page' => $antecedents->lastPage(),
            'total' => $antecedents->total(),
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission OpsTblAntecedentController::store
     * @permission_desc Création des antécédants d'un client
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $messages = [
            'client_id.required' => 'Le client est obligatoire.',
            'client_id.exists' => 'Le client sélectionné est invalide.',
            'categorie_antecedent_id.required' => 'La catégorie est obligatoire.',
            'categorie_antecedent_id.exists' => 'La catégorie sélectionnée est invalide.',
            'souscategorie_antecedent_id.required' => 'La sous-catégorie est obligatoire.',
            'souscategorie_antecedent_id.exists' => 'La sous-catégorie sélectionnée est invalide.',
            'description.string' => 'La description doit être une chaîne de caractères.',
        ];

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'categorie_antecedent_id' => 'required|exists:config_tbl_categorie_antecedents,id',
            'souscategorie_antecedent_id' => 'required|exists:configtbl_souscategorie_antecedent,id',
            'description' => 'nullable|string',
        ], $messages);

        $validated['created_by'] = $auth ? $auth->id : null;

        $antecedent = OpsTblAntecedent::create($validated);
        $antecedent->load(['createdBy', 'updatedBy', 'client', 'categorie', 'sousCategorie']);

        return response()->json([
            'data' => $antecedent,
            'status' => 'success',
            'message' => 'Antécédent enregistré avec succès.'
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission OpsTblAntecedentController::update
     * @permission_desc Modification des antécédants d'un client
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        $messages = [
            'client_id.required' => 'Le client est obligatoire.',
            'client_id.exists' => 'Le client sélectionné est invalide.',
            'categorie_antecedent_id.required' => 'La catégorie est obligatoire.',
            'categorie_antecedent_id.exists' => 'La catégorie sélectionnée est invalide.',
            'souscategorie_antecedent_id.required' => 'La sous-catégorie est obligatoire.',
            'souscategorie_antecedent_id.exists' => 'La sous-catégorie sélectionnée est invalide.',
            'description.string' => 'La description doit être une chaîne de caractères.',
        ];

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'categorie_antecedent_id' => 'required|exists:config_tbl_categorie_antecedents,id',
            'souscategorie_antecedent_id' => 'required|exists:configtbl_souscategorie_antecedent,id',
            'description' => 'nullable|string',
        ], $messages);

        $antecedent = OpsTblAntecedent::findOrFail($id);

        $validated['updated_by'] = $auth ? $auth->id : null;

        $antecedent->update($validated);
        $antecedent->load(['createdBy', 'updatedBy', 'client', 'categorie', 'sousCategorie']);

        return response()->json([
            'data' => $antecedent,
            'status' => 'success',
            'message' => 'Antécédent modifié avec succès.'
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission OpsTblAntecedentController::show
     * @permission_desc Afficher  les détails des antécédants d'un client
     */
    public function show($id)
    {
        $antecedent = OpsTblAntecedent::with(['client', 'categorie', 'sousCategorie','createdBy','updatedBy'])->find($id);

        if (!$antecedent) {
            return response()->json([
                'status' => 'error',
                'message' => 'Antécédent non trouvé.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $antecedent
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission OpsTblAntecedentController::destroy
     * @permission_desc Supprimer les antécédants d'un client
     */

    public function destroy($id)
    {
        $antecedent = OpsTblAntecedent::find($id);

        if (!$antecedent) {
            return response()->json([
                'status' => 'error',
                'message' => "L'antécédent n'existe pas."
            ], 404);
        }

        $antecedent->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Antécédent supprimé avec succès."
        ]);
    }



    //
}
