<?php

namespace App\Http\Controllers;

use App\Models\Assurable;
use App\Models\Assureur;
use App\Models\Centre;
use App\Models\Soins;
use App\Models\TypeActe;
use App\Models\TypeSoins;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


/**
 * @permission_category Gestion des soins
 * @permission_module Paramètres Facturations
 */

class SoinsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission SoinsController::index
     * @permission_desc Afficher la liste des soins
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);
        $search = $request->input('search');

        $query = Soins::with(['type_soins', 'creator', 'updater'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%$search%")
                        ->orWhereHas('type_soins', function ($rel) use ($search) {
                            $rel->where('name', 'like', "%$search%");
                        });
                });
            })
            ->orderBy('name', 'asc')
            ->paginate(perPage: $perPage, page: $page);

        return response()->json([
            'data' => $query->items(),
            'current_page' => $query->currentPage(),
            'last_page' => $query->lastPage(),
            'total' => $query->total(),
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission SoinsController::store
     * @permission_desc Créer un soin
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'type_soin_id' => 'required|exists:type_soins,id',
                'pu' => 'required|integer|min:0',
                'pu_default' => 'required|integer|min:0',
                'name' => 'required|string|max:255|unique:soins,name',
                'is_used_for_commission' => 'nullable|boolean',
                'is_active' => 'sometimes|boolean',
            ]);

            DB::beginTransaction();

            $data['created_by'] = $request->user()?->id ?? auth()->id();
            $data['is_used_for_commission'] = $data['is_used_for_commission'] ?? false;
            $data['is_active'] = $data['is_active'] ?? true;

            $soin = Soins::create($data);

            DB::commit();

            return response()->json([
                'data' => $soin->load(['type_soins', 'creator', 'updater']),
                'message' => 'Soin enregistré avec succès',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission SoinsController::show
     * @permission_desc Afficher les détails d'un soins
     */
    public function show($id)
    {
        try {
            $soin = Soins::with(['type_soins', 'creator', 'updater'])->findOrFail($id);

            return response()->json([
                'data' => $soin,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Ressource introuvable',
                'message' => 'Soin introuvable',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission SoinsController::update
     * @permission_desc Mettre à jour un soin
     */
    public function update(Request $request, $id)
    {
        try {
            $soin = Soins::findOrFail($id);

            $data = $request->validate([
                'type_soin_id' => 'sometimes|required|exists:type_soins,id',
                'pu' => 'sometimes|required|integer|min:0',
                'pu_default' => 'sometimes|required|integer|min:0',
                'name' => 'sometimes|required|string|max:255|unique:soins,name,' . $soin->id,
                'is_used_for_commission' => 'nullable|boolean',
                'is_active' => 'sometimes|boolean',
            ]);

            DB::beginTransaction();

            $data['updated_by'] = $request->user()?->id ?? auth()->id();

            $soin->update($data);

            DB::commit();

            return response()->json([
                'data' => $soin->fresh(['type_soins', 'creator', 'updater']),
                'message' => 'Soin mis à jour avec succès',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Ressource introuvable',
                'message' => 'Soin introuvable',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     * @permission SoinsController::updateStatus
     * @permission_desc Activer/Désactiver un soin
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $soin = Soins::findOrFail($id);

            DB::beginTransaction();

            $soin->is_active = ! $soin->is_active;
            $soin->updated_by = $request->user()?->id ?? auth()->id();
            $soin->save();

            DB::commit();

            return response()->json([
                'data' => $soin->fresh(['type_soins', 'creator', 'updater']),
                'message' => $soin->is_active
                    ? 'Statut activé avec succès'
                    : 'Statut désactivé avec succès',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Ressource introuvable',
                'message' => 'Soin introuvable',
            ], 404);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission SoinsController::Tarfication_Soins
     * @permission_desc Imprimer la tarification des soins
     */
    public function Tarfication_Soins(Request $request)
    {
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => 'Centre non fourni'
            ], 400);
        }

        $centre = Centre::find($centreId);

        if (!$centre) {
            return response()->json([
                'message' => 'Centre introuvable'
            ], 404);
        }

        if (!$centre->is_acte_recommendation_center) {
            return response()->json([
                'message' => "Impossible d'imprimer, veuillez vous connecter au centre médical"
            ], 403);
        }

        DB::beginTransaction();

        try {
            $media = $centre->medias()->where('name', 'logo')->first();

            $types = TypeSoins::with([
                'soins' => function ($query) {
                    $query->where('is_active', true)
                        ->orderBy('name', 'asc');
                }
            ])
                ->where('is_active', true)
                ->orderBy('order', 'asc')
                ->get()
                ->filter(fn($type) => $type->soins->count() > 0)
                ->values();

            $data = [
                'title'  => 'Tarifaire des soins',
                'types'  => $types,
                'logo'   => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
                'centre' => $centre,
            ];

            $fileName   = strtoupper('rapport-soins-' . now()->format('YmdHis') . '.pdf');
            $folderPath = 'storage/rapport-soins';
            $filePath   = $folderPath . '/' . $fileName;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            save_browser_shot_pdf(
                view: 'pdfs.rapport-soins.rapports-soins',
                data: $data,
                folderPath: $folderPath,
                path: $filePath,
                margins: [5, 5, 5, 5],
                format: 'A4',
            );

            if (!file_exists($filePath)) {
                DB::rollBack();
                return response()->json([
                    'error' => "Le fichier PDF n'a pas été généré."
                ], 500);
            }

            DB::commit();

            $pdfContent = file_get_contents($filePath);
            $base64 = base64_encode($pdfContent);

            return response()->json([
                'base64'   => $base64,
                'url'      => $filePath,
                'filename' => $fileName,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
