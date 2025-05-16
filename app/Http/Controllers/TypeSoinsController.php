<?php

namespace App\Http\Controllers;

use App\Models\TypeSoins;
use Illuminate\Http\Request;
use Sabberworm\CSS\Rule\Rule;

class TypeSoinsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission TypeSoinsController::listIdName
     * @permission_desc Afficher l'id et le nom des types de soins
     */

    public function listIdName(){
        $data = TypeSoins::select('id','name')->where('is_deleted',false)->get();
        return response()->json([
            'type_soins' => $data
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission TypeSoinsController::index
     * @permission_desc Afficher la liste des soins
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 5);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante
        $type_soins = TypeSoins::where('is_deleted',false)->paginate($perPage);
        return response()->json([
            'data' => $type_soins->items(),
            'current_page' => $type_soins->currentPage(),  // Page courante
            'last_page' => $type_soins->lastPage(),  // Dernière page
            'total' => $type_soins->total(),  // Nombre total d'éléments
        ]);

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
     * Display a listing of the resource.
     * @permission TypeSoinsController::store
     * @permission_desc Créer des soins
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        $data = $request->validate([
            'name' => 'required|string|unique:type_soins,name'
        ]);
        $data['created_by'] = $auth->id;
        $type_soins = TypeSoins::create($data);
        return response()->json([
            'data' => $type_soins,
            'message'=> 'Enregistrement effectué avec succès'
        ]);
        //
    }

    /**
     * Display a listing of the resource.
     * @permission TypeSoinsController::show
     * @permission_desc Afficher les détails des soins
     */
    public function show(string $id)
    {
        $type_soins = TypeSoins::where('id', $id)->where('is_deleted', false)->first();
        if(!$type_soins){
            return response()->json(['message' => 'Le type de soins n\'existe pas',404]);
        }else{
            return response()->json($type_soins);
        }
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
     * Display a listing of the resource.
     * @permission TypeSoinsController::show
     * @permission_desc Mise à jour des soins
     */
    public function update(Request $request, string $id)
    {
        $auth = auth()->user();

        $type_soins = TypeSoins::where('id', $id)->where('is_deleted', false)->first();

        // Valider les données reçues
        $data = $request->validate([
            'name' => 'required|string|unique:type_soins,name,' . $id,  // Autoriser la modification du nom, mais éviter la duplication
        ]);
        $data['updated_by'] = $auth->id;

        // Mettre à jour les données
        $type_soins->update($data);

        // Retourner la réponse avec les données mises à jour
        return response()->json([
            'data' => $type_soins,
            'message' => 'Mise à jour effectuée avec succès'
        ]);
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
