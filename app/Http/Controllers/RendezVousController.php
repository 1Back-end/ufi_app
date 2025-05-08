<?php

namespace App\Http\Controllers;

use App\Exports\PrisesEnChargeExport;
use App\Exports\RendezVousExport;
use App\Models\RendezVous;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class RendezVousController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request){
        $perPage = $request->input('limit', 10);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante

        // Récupérer les assureurs avec pagination
        $rendez_vous = RendezVous::where('is_deleted', false)
            ->with(
                'client:id,nomcomplet_client',
                'consultant:id,nomcomplet',
                'createdBy:id,email',
                'updatedBy:id,email'
            )
            ->paginate($perPage);

        return response()->json([
            'data' => $rendez_vous->items(),
            'current_page' => $rendez_vous->currentPage(),  // Page courante
            'last_page' => $rendez_vous->lastPage(),  // Dernière page
            'total' => $rendez_vous->total(),  // Nombre total d'éléments
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        try {
            // Validation des données entrantes
            $data = $request->validate([
                'client_id' => 'required|integer|exists:clients,id',
                'consultant_id' => 'required|exists:consultants,id',
                'dateheure_rdv' => 'required|date',
                'heure_debut' => 'required|date_format:H:i',
                'heure_fin' => 'required|date_format:H:i|after:heure_debut',
                'details' => 'required|string',
                'nombre_jour_validite' => 'required|integer',
            ]);

            // Vérifie que le consultant n'a pas déjà un RDV qui chevauche
            $rdvDate = \Carbon\Carbon::parse($data['dateheure_rdv'])->toDateString();

            $hasConflict = RendezVous::where('consultant_id', $data['consultant_id'])
                ->whereDate('dateheure_rdv', $rdvDate)
                ->where(function ($query) use ($data) {
                    $query->where(function ($q) use ($data) {
                        $q->where('heure_debut', '<', $data['heure_fin'])
                            ->where('heure_fin', '>', $data['heure_debut']);
                    });
                })
                ->exists();

            if ($hasConflict) {
                return response()->json([
                    'message' => 'Le consultant a déjà un rendez-vous dans cette plage horaire.',
                ], 400);
            }

            // Vérifie si un RDV existe déjà pour le client ce jour-là (optionnel)
            $existingRdvClient = RendezVous::where('client_id', $data['client_id'])
                ->whereDate('dateheure_rdv', $rdvDate)
                ->exists();

            if ($existingRdvClient) {
                return response()->json([
                    'message' => 'Un rendez-vous est déjà prévu pour ce client à cette date.',
                ], 400);
            }

            // Ajoute les champs manquants
            $data['date_emission'] = now();
            $data['created_by'] = $auth->id;

            // Création du rendez-vous
            $rendez_vous = RendezVous::create($data);

            return response()->json([
                'data' => $rendez_vous,
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


    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        try {
            // Validation des données entrantes
            $data = $request->validate([
                'client_id' => 'required|integer|exists:clients,id',
                'consultant_id' => 'required|exists:consultants,id',
                'dateheure_rdv' => 'required|date',
                'heure_debut' => 'required|date_format:H:i',
                'heure_fin' => 'required|date_format:H:i|after:heure_debut',
                'details' => 'required|string',
                'nombre_jour_validite' => 'required|integer',
            ]);

            // Récupère le rendez-vous à modifier
            $rendezVous = RendezVous::findOrFail($id);

            $rdvDate = \Carbon\Carbon::parse($data['dateheure_rdv'])->toDateString();

            // Vérifie le chevauchement des plages horaires du consultant (sauf ce rendez-vous)
            $hasConflict = RendezVous::where('consultant_id', $data['consultant_id'])
                ->whereDate('dateheure_rdv', $rdvDate)
                ->where('id', '!=', $rendezVous->id)
                ->where(function ($query) use ($data) {
                    $query->where('heure_debut', '<', $data['heure_fin'])
                        ->where('heure_fin', '>', $data['heure_debut']);
                })
                ->exists();

            if ($hasConflict) {
                return response()->json([
                    'message' => 'Le consultant a déjà un rendez-vous dans cette plage horaire.',
                ], 400);
            }

            // Vérifie s’il existe un autre rendez-vous le même jour pour ce client (hors lui-même)
            $existingClientRdv = RendezVous::where('client_id', $data['client_id'])
                ->whereDate('dateheure_rdv', $rdvDate)
                ->where('id', '!=', $rendezVous->id)
                ->exists();

            if ($existingClientRdv) {
                return response()->json([
                    'message' => 'Un autre rendez-vous est déjà prévu pour ce client à cette date.',
                ], 400);
            }

            // Mise à jour des données
            $data['updated_by'] = $auth->id;
            $rendezVous->update($data);

            return response()->json([
                'data' => $rendezVous,
                'message' => 'Rendez-vous mis à jour avec succès'
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



    public function updateStatus(Request $request, $id,$etat)
    {
        $rendez_vous = RendezVous::find($id);
        if (!$rendez_vous) {
            return response()->json(['message' => 'Rendez vous non trouvé'], 404);
        }
        // Check if the assureur is deleted
        if ($rendez_vous->is_deleted) {
            return response()->json(['message' => 'Impossible de mettre à jour un rendez supprimé'], 400);
        }
        // Validate the status
        if (!in_array($etat, ['Actif','Inactif','Clos', 'No show'])) {
            return response()->json(['message' => 'Type invalide'], 400);
        }
        // Update the status
        $rendez_vous->etat = $etat;  // Ensure the correct field name
        $rendez_vous->save();

        // Return the updated assureur
        return response()->json([
            'message' => 'Etat mis à jour avec succès',
            'rendez vous' => $rendez_vous // Corrected to $assureur
        ], 200);
    }
    public function toggleType(Request $request, $id, $type)
    {
        // Find the assureur by ID
        $rendez_vous = RendezVous::find($id);
        if (!$rendez_vous) {
            return response()->json(['message' => 'Rendez vous non trouvé'], 404);
        }

        // Check if the assureur is deleted
        if ($rendez_vous->is_deleted) {
            return response()->json(['message' => 'Impossible de mettre à jour un rendez supprimé'], 400);
        }

        // Validate the status
        if (!in_array($type, ['Facturé', 'Non Facturé'])) {
            return response()->json(['message' => 'Type invalide'], 400);
        }

        // Update the status
        $rendez_vous->type = $type;  // Ensure the correct field name
        $rendez_vous->save();

        // Return the updated assureur
        return response()->json([
            'message' => 'Type mis à jour avec succès',
            'rendez vous' => $rendez_vous // Corrected to $assureur
        ], 200);
    }
    public function export()
    {
        try {
            $fileName = 'rendez-vous-' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx';
            // Tentative d'exportation des données
            Excel::store(new RendezVousExport(), $fileName, 'exportrendezvous');
            // Retourner la réponse si l'exportation réussit
            return response()->json([
                "message" => "Exportation des données effectuée avec succès",
                "filename" => $fileName,
                "url" => Storage::disk('exportrendezvous')->url($fileName)
            ]);
        } catch (\Exception $e) {
            // Si une erreur survient, on la capture et on retourne un message d'erreur
            return response()->json([
                "message" => "Une erreur est survenue lors de l'exportation des données.",
                "error" => $e->getMessage()  // On inclut le message d'erreur pour plus de détails
            ], 500); // Code de statut 500 pour une erreur serveur
        }
    }

    public function search(Request $request)
    {
        $request->validate([
            'query' => 'nullable|string|max:255',
        ]);

        $searchQuery = $request->input('query', '');

        $query = RendezVous::where('is_deleted', false);

        if ($searchQuery) {
            $query->where(function($q) use ($searchQuery) {
                $q->where('details', 'like', '%' . $searchQuery . '%')
                    ->orWhere('nombre_jour_validite', 'like', '%' . $searchQuery . '%')
                    ->orWhere('type', 'like', '%' . $searchQuery . '%')
                    ->orWhere('etat', 'like', '%' . $searchQuery . '%')
                    ->orWhere('code', 'like', '%' . $searchQuery . '%')
                    ->orWhereHas('client', function ($subQ) use ($searchQuery) {
                        $subQ->where('nomcomplet_client', 'like', '%' . $searchQuery . '%');
                    })
                    ->orWhereHas('consultant', function ($subQ) use ($searchQuery) {
                        $subQ->where('nomcomplet', 'like', '%' . $searchQuery . '%');
                    });
            });
        }

        $rendez_vous = $query
            ->with([
                'client:id,nomcomplet_client',
                'consultant:id,nomcomplet'
            ])
            ->get();

        return response()->json([
            'data' => $rendez_vous,
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $rendez_vous = RendezVous::where('is_deleted', false)
                ->with([
                    'client:id,nomcomplet_client',
                    'consultant:id,nomcomplet',
                ])
                ->findOrFail($id);
            return response()->json($rendez_vous);
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        //
    }
}
