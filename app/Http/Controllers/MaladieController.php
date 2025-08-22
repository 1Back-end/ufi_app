<?php

namespace App\Http\Controllers;
use App\Imports\MaladieImport;
use App\Models\Maladie;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;


class MaladieController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission MaladieController::store
     * @permission_desc Création de maladies
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        $request->validate([
            'classe_maladie_id' => 'required|exists:classe_maladie,id',
            'groupe_maladie_id' => 'required|exists:groupes_maladies,id',
            'code' => 'required|string|max:50|unique:maladies,code',
            'name' => 'required|string|max:255',
        ]);

        $maladie = Maladie::create([
            'classe_maladie_id' => $request->classe_maladie_id,
            'groupe_maladie_id' => $request->groupe_maladie_id,
            'code' => $request->code,
            'name' => $request->name,
            'created_by' => $auth->id
        ]);

        return response()->json([
            'message' => 'Maladie créée avec succès.',
            'data' => $maladie,
        ], 201);
    }

    public function import(Request $request)
    {

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new MaladieImport, $request->file('file'));
            return response()->json(['message' => 'Importation réussie.']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }



    /**
     * Display a listing of the resource.
     * @permission MaladieController::update
     * @permission_desc Modification de maladies
     */

    public function update(Request $request, $id)
    {
        $request->validate([
            'classe_maladie_id' => 'required|exists:classe_maladie,id',
            'groupe_maladie_id' => 'required|exists:groupes_maladies,id',
            'code' => 'required|string|max:50|unique:maladies,code,' . $id,
            'name' => 'required|string|max:255',
        ]);

        $maladie = Maladie::findOrFail($id);

        $maladie->update([
            'classe_maladie_id' => $request->classe_maladie_id,
            'groupe_maladie_id' => $request->groupe_maladie_id,
            'code' => $request->code,
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Maladie mise à jour avec succès.',
            'data' => $maladie,
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission MaladieController::update
     * @permission_desc Afficher les détails de maladies
     */
    public function show($id)
    {
        $maladie = Maladie::with(['classeMaladie', 'groupeMaladie','creator','updater'])->findOrFail($id);

        return response()->json([
            'message' => 'Détails de la maladie.',
            'data' => $maladie,
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission MaladieController::index
     * @permission_desc Afficher la liste de maladies
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = Maladie::where('is_deleted', false)
            ->with(['classeMaladie', 'groupeMaladie', 'creator', 'updater']);

        // Filtrage par classe_maladie_id
        if ($request->filled('classe_maladie_id')) {
            $query->where('classe_maladie_id', $request->input('classe_maladie_id'));
        }

        // Filtrage par groupe_maladie_id
        if ($request->filled('groupe_maladie_id')) {
            $query->where('groupe_maladie_id', $request->input('groupe_maladie_id'));
        }

        // Recherche globale
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('classeMaladie', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('groupeMaladie', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('id', 'like', "%{$search}%");
                    });
            });
        }

        $results = $query->latest()->paginate(perPage: $perPage, page: $page);

        return response()->json([
            'data' => $results->items(),
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
            'total' => $results->total(),
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission MaladieController::updateStatus
     * @permission_desc Changer le statut  de maladies
     */
    public function updateStatus(Request $request, $id)
    {
        $auth = auth()->user();
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $maladie = Maladie::where('is_deleted', false)->find($id);
        $maladie->is_active = $request->is_active;
        $maladie->updated_by = $auth->id;
        $maladie->save();

        return response()->json([
            'message' => 'Statut mis à jour avec succès.',
            'data' => $maladie
        ]);
    }




    //
}
