<?php

namespace App\Http\Controllers;

use App\Exports\FournisseurExport;
use App\Exports\PrisesEnChargeExport;
use App\Exports\PrisesEnChargeExportSearch;
use App\Models\Client;
use App\Models\Prestation;
use App\Models\PriseEnCharge;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;


/**
 * @permission_category Gestion des prises en charges
 * @permission_module Gestion des prestations
 */
class PriseEnChargeController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission PriseEnChargeController::export
     * @permission_desc Exporter les données des prises en charges
     */
    public function export()
    {
        try {
            $fileName = 'prises-en-charges' . Carbon::now()->format('Y-m-d') . '.xlsx';

            Excel::store(new PrisesEnChargeExport(), $fileName, 'exportprisesencharges');

            // Retourner la réponse si l'exportation réussit
            return response()->json([
                "message" => "Exportation des données effectuée avec succès",
                "filename" => $fileName,
                "url" => Storage::disk('exportprisesencharges')->url($fileName)
            ]);
        } catch (\Exception $e) {
            // Si une erreur survient, on la capture et on retourne un message d'erreur
            return response()->json([
                "message" => "Une erreur est survenue lors de l'exportation des données.",
                "error" => $e->getMessage()  // On inclut le message d'erreur pour plus de détails
            ], 500); // Code de statut 500 pour une erreur serveur
        }
    }

    /**
     * Display a listing of the resource.
     * @permission PriseEnChargeController::index
     * @permission_desc Afficher la liste des prises en charges
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $search = $request->input('search');
        $clientId = $request->input('client');
        $assureurFilter = $request->input('assureur');

        $prise_en_charges = PriseEnCharge::with([
            'assureur',
            'quotation',
            'client',
            'creator',
            'updater'
        ])
            ->when($clientId, function ($query) use ($clientId, $assureurFilter) {
                $query->with([
                    'assureur.actes',
                    'assureur.soins',
                    'assureur.consultations',
                    'assureur.hospitalisations'
                ])
                    ->where('client_id', $clientId)
                    ->whereDate('date_debut', '<=', now())
                    ->whereDate('date_fin', '>=', now())
                    ->where('used', false)
                    ->when($assureurFilter, function ($q) use ($assureurFilter) {
                        $q->whereHas('assureur', function ($subQuery) use ($assureurFilter) {
                            $subQuery->where('nom', 'like', '%' . $assureurFilter . '%')
                                ->orWhere('nom_abrege', 'like', '%' . $assureurFilter . '%')
                                ->orWhere('adresse', 'like', '%' . $assureurFilter . '%')
                                ->orWhere('tel1', 'like', '%' . $assureurFilter . '%');
                        });
                    });
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('client', function ($qClient) use ($search) {
                        $qClient->where('prenom_cli', 'like', "%$search%")
                            ->orWhere('nom_cli', 'like', "%$search%")
                            ->orWhere('secondprenom_cli', 'like', "%$search%")
                            ->orWhere('nomcomplet_client', 'like', "%$search%")
                            ->orWhere('ref_cli', 'like', "%$search%");
                    })
                        ->orWhereHas('assureur', function ($qAssureur) use ($search) {
                            $qAssureur->where('nom', 'like', "%$search%")
                                ->orWhere('nom_abrege', 'like', "%$search%")
                                ->orWhere('adresse', 'like', "%$search%")
                                ->orWhere('tel1', 'like', "%$search%")
                                ->orWhere('code', 'like', "%$search%");
                        });
                });
            })
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $prise_en_charges->items(),
            'current_page' => $prise_en_charges->currentPage(),
            'last_page' => $prise_en_charges->lastPage(),
            'total' => $prise_en_charges->total(),
        ]);
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
     * @permission PriseEnChargeController::store
     * @permission_desc Créer  des prises en charges
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $data = $request->validate([
            'assureur_id'  => 'required|exists:assureurs,id',
            'quotation_id' => 'required|exists:quotations,id',
            'code'         => 'required|string',
            'date_debut'   => 'required|date',
            'date_fin'     => 'required|date',
            'client_id'    => 'required|exists:clients,id',
            'taux_pc'      => 'required|integer',
            'usage_unique' => 'boolean',
        ]);

        // Validation de la date de début
        if (strtotime($data['date_debut']) < strtotime(date('Y-m-d'))) {
            return response()->json([
                'message' => 'La date de début ne peut pas être antérieure à la date d\'aujourd\'hui.'
            ], 400);
        }

        if (strtotime($data['date_fin']) <= strtotime($data['date_debut'])) {
            return response()->json([
                'message' => 'La date de fin doit être supérieure à la date de début.'
            ], 400);
        }

        $overlapping = PriseEnCharge::where('assureur_id', $data['assureur_id'])
            ->where('client_id', $data['client_id'])
            ->where(function ($query) use ($data) {
                $query->whereBetween('date_debut', [$data['date_debut'], $data['date_fin']])
                    ->orWhereBetween('date_fin', [$data['date_debut'], $data['date_fin']])
                    ->orWhere(function ($q) use ($data) {
                        $q->where('date_debut', '<=', $data['date_debut'])
                            ->where('date_fin', '>=', $data['date_fin']);
                    });
            })
            ->exists();

        if ($overlapping) {
            return response()->json([
                'message' => 'Une prise en charge existe déjà pour ce client et cet assureur sur cette période.'
            ], 400);
        }

        $data['created_by'] = $auth->id;

        $prise_en_charge = PriseEnCharge::create($data);

        return response()->json([
            'data' => $prise_en_charge,
            'message' => 'Prise en charge enregistrée avec succès'
        ], 201);
    }





    /**
     * Display a listing of the resource.
     * @permission PriseEnChargeController::show
     * @permission_desc Afficher  les details d'une prise en charges
     */
    public function show(string $id)
    {
        try {
            $prise_en_charge = PriseEnCharge::where('is_deleted', false)
                ->with([
                    'assureur',
                    'quotation',
                    'client'
                ])
                ->findOrFail($id);
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
     * Display a listing of the resource.
     * @permission PriseEnChargeController::update
     * @permission_desc Mettre à jour des prises en charges
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        $priseEnCharge = PriseEnCharge::find($id);
        if (!$priseEnCharge) {
            return response()->json([
                'message' => 'Prise en charge introuvable.'
            ], 404);
        }

        $data = $request->validate([
            'assureur_id'  => 'required|exists:assureurs,id',
            'quotation_id' => 'required|exists:quotations,id',
            'code'         => 'required|string',
            'date_debut'   => 'required|date',
            'date_fin'     => 'required|date',
            'client_id'    => 'required|exists:clients,id',
            'taux_pc'      => 'required|integer',
            'usage_unique' => 'boolean',
        ]);

        if (strtotime($data['date_debut']) < strtotime(date('Y-m-d'))) {
            return response()->json([
                'message' => 'La date de début ne peut pas être antérieure à la date d\'aujourd\'hui.'
            ], 400);
        }

        if (strtotime($data['date_fin']) <= strtotime($data['date_debut'])) {
            return response()->json([
                'message' => 'La date de fin doit être supérieure à la date de début.'
            ], 400);
        }

        $overlapping = PriseEnCharge::where('assureur_id', $data['assureur_id'])
            ->where('client_id', $data['client_id'])
            ->where('id', '!=', $id)
            ->where(function ($query) use ($data) {
                $query->whereBetween('date_debut', [$data['date_debut'], $data['date_fin']])
                    ->orWhereBetween('date_fin', [$data['date_debut'], $data['date_fin']])
                    ->orWhere(function ($q) use ($data) {
                        $q->where('date_debut', '<=', $data['date_debut'])
                            ->where('date_fin', '>=', $data['date_fin']);
                    });
            })
            ->exists();

        if ($overlapping) {
            return response()->json([
                'message' => 'Une autre prise en charge existe déjà pour ce client et cet assureur sur cette période.'
            ], 400);
        }


        $data['updated_by'] = $auth->id;

        $priseEnCharge->update($data);

        return response()->json([
            'data' => $priseEnCharge,
            'message' => 'Prise en charge modifiée avec succès'
        ], 200);
    }


    /**
     * Display a listing of the resource.
     * @permission PriseEnChargeController::destroy
     * @permission_desc Supprimer les données des prises en charges
     */
    public function destroy(string $id)
    {
        try {
            $prise_en_charge = PriseEnCharge::where('is_deleted', false)->findOrFail($id);

            // Vérifier s'il existe des prestations liées à cette prise en charge
            $hasPrestations = $prise_en_charge->prestations()->exists();

            if ($hasPrestations) {
                return response()->json([
                    'message' => 'Impossible de supprimer : des prestations sont liées à cette prise en charge.'
                ], 400);
            }
            // Marquer comme supprimée
            $prise_en_charge->update([
                'is_deleted' => true
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

    /**
     * Display a listing of the resource.
     * @permission PriseEnChargeController::searchAndExport
     * @permission_desc Rechercher et Exporter les données des prises en charges
     */
    public function searchAndExport(Request $request)
    {
        // Validation du paramètre de recherche
        $request->validate([
            'query' => 'nullable|string|max:255',
        ]);

        // Récupérer la requête de recherche
        $searchQuery = $request->input('query', '');

        // Initialisation de la requête
        $query = PriseEnCharge::where('is_deleted', false);

        // Appliquer les filtres si une requête de recherche est fournie
        if ($searchQuery) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('taux_pc', 'like', '%' . $searchQuery . '%')
                    ->orWhere('date', 'like', '%' . $searchQuery . '%')
                    ->orWhere('date_debut', 'like', '%' . $searchQuery . '%')
                    ->orWhere('date_fin', 'like', '%' . $searchQuery . '%')
                    ->orWhere('usage_unique', 'like', '%' . $searchQuery . '%');
            });
        }

        $prises_en_charges = $query
            ->with(['client', 'assureur', 'quotation']) // chargement des relations
            ->get();

        // Vérifier si la collection est vide
        if ($prises_en_charges->isEmpty()) {
            return response()->json([
                'message' => 'Aucun élément trouvé pour cette recherche.',
                'data' => []
            ], 404);
        }

        try {
            // Nom du fichier avec la date actuelle
            $fileName = 'prises-en-charges-recherche-' . Carbon::now()->format('Y-m-d') . '.xlsx';

            // Exporter les données vers un fichier Excel
            Excel::store(new PrisesEnChargeExportSearch($prises_en_charges), $fileName, 'exportprisesencharges');

            return response()->json([
                'message' => 'Exportation des données effectuée avec succès.',
                'filename' => $fileName,
                'url' => Storage::disk('exportprisesencharges')->url($fileName),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'exportation des données.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     * @permission PriseEnChargeController::searchAndExport
     * @permission_desc Rechercher  des prises en charges
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'nullable|string|max:255',
        ]);

        $searchQuery = $request->input('query', '');

        $query = PriseEnCharge::where('is_deleted', false);

        if ($searchQuery) {
            $query->where(function ($query) use ($searchQuery) {
                $query->where('taux_pc', 'like', '%' . $searchQuery . '%')
                    ->orWhere('date', 'like', '%' . $searchQuery . '%')
                    ->orWhere('date_debut', 'like', '%' . $searchQuery . '%')
                    ->orWhere('date_fin', 'like', '%' . $searchQuery . '%')
                    ->orWhere('usage_unique', 'like', '%' . $searchQuery . '%');
            });
        }

        $prises_en_charges = $query
            ->with(['client', 'assureur', 'quotation']) // chargement des relations
            ->get();

        return response()->json([
            'data' => $prises_en_charges,
        ]);
    }
}
