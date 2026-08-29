<?php

namespace App\Http\Controllers;

use App\Exports\ExportPackaging;
use App\Models\Packaging;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel;

/**
 * @permission_category Gestion des conditionnements produits
 * @permission_module Gestion des stocks
 */
class PackagingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission PackagingController::index
     * @permission_desc Afficher la liste des conditionnements produits
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 5);
        $page = $request->input('page', 1);

        $query = Packaging::with(['creator:id,nom_utilisateur', 'updater:id,nom_utilisateur'])
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            });

        if($search = trim($request->input('search'))){
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }
        $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page'    => $data->lastPage(),
            'total'        => $data->total(),
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission PackagingController::store
     * @permission_desc Créer un conditionnement de produits
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:255|unique:packagings,code',
            'quantity'    => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        try {
            $validated['name'] = Str::upper($validated['name']);

            if (!empty($validated['code'])) {
                $validated['code'] = Str::upper($validated['code']);
            }

            $validated['is_active'] = $validated['is_active'] ?? true;
            $validated['created_by'] = $auth?->id;
            $validated['updated_by'] = $auth?->id;

            $packaging = Packaging::create($validated);

            return response()->json([
                'message' => 'Conditionnement créé avec succès.',
                'data'    => $packaging
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Erreur création conditionnement : ' . $e->getMessage());

            return response()->json([
                'message' => 'Une erreur est survenue lors de la création du conditionnement.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission PackagingController::show
     * @permission_desc Afficher les détails d'un conditionnement produit
     */
    public function show($id)
    {
        $packaging = Packaging::with(['creator', 'updater'])->findOrFail($id);

        return response()->json([
            'message' => 'Détails du conditionnement récupérés avec succès.',
            'data' => $packaging
        ], 200);
    }

    /**
     * Display a listing of the resource.
     * @permission PackagingController::update
     * @permission_desc Modifier un conditionnement de produits
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();
        $packaging = Packaging::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:255|unique:packagings,code,' . $id,
            'quantity'    => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        try {
            $validated['name'] = Str::upper($validated['name']);

            // Sécurité pour le code s'il est facultatif
            if (!empty($validated['code'])) {
                $validated['code'] = Str::upper($validated['code']);
            }

            $validated['updated_by'] = $auth?->id;

            $packaging->update($validated);

            return response()->json([
                'message' => 'Conditionnement mis à jour avec succès.',
                'data'    => $packaging
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour conditionnement : ' . $e->getMessage());

            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour du conditionnement.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission PackagingController::updateStatus
     * @permission_desc Activer/Désactiver un conditionnement de produits
     */
    public function updateStatus(Request $request, $id)
    {
        $packaging = Packaging::findOrFail($id);
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);
        $validated['updated_by'] = auth()->id();
        $packaging->update($validated);
        return response()->json([
            'message' => 'Statut du conditionnement mis à jour avec succès.',
            'data' => $packaging
        ], 200);
    }


    /**
     * Display a listing of the resource.
     * @permission PackagingController::export_in_excel
     * @permission_desc Exporter la liste des conditionnements de produits en excel
     */
    public function export_in_excel(Request $request)
    {
        try {
            $fileName = strtoupper('LISTE-DES-CONDITIONNEMENTS-' . Carbon::now()->format('Y-m-d') . '.xlsx');

            \Maatwebsite\Excel\Facades\Excel::store(new ExportPackaging(), $fileName, 'productspackaging');

            return response()->json([
                "message" => "Exportation des données effectuée avec succès",
                "filename" => $fileName,
                "url" => Storage::disk('productspackaging')->url($fileName)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Erreur lors de l'exportation des données",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Packaging $packaging)
    {
        //
    }
}
