<?php

namespace App\Http\Controllers;

use App\Enums\TypePrestation;
use App\Models\Campagne;
use App\Models\CampagneFacture;
use App\Models\Centre;
use App\Models\Facture;
use App\Models\Prestation;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CampagneFactureController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission CampagneFactureController::index
     * @permission_desc Afficher la liste des campagnes facturées
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);  // Par défaut 25 éléments par page
        $page = $request->input('page', 1);

        $query = CampagneFacture::with([
            'campagne',
            'creator',
            'updater',
            'centre',
            'patient',
            'consultant',

        ])
            ->where('centre_id', $request->header('centre'));

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('patient_id')) $query->where('patient_id', $request->patient_id);
        if ($request->filled('consultant_id')) $query->where('consultant_id', $request->consultant_id);
        if ($request->filled('campagne_id')) $query->where('campagne_id', $request->campagne_id);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = \Illuminate\Support\Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();

            $query->whereBetween('created_at', [$start, $end]);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%$search%")
                    ->orWhere('amount', 'like', "%$search%")
                    ->orWhere('status', 'like', "%$search%");
            });
        }

        // Exécution de la requête avec pagination
        $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        // Retour de la réponse JSON
        return response()->json([
            'data' => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'total' => $data->total(),
        ]);

    }

    /**
     * Display a listing of the resource.
     * @permission CampagneFactureController::store
     * @permission_desc Facturer une campagne
     */
    public function store(Request $request)
    {
        $auth     = auth()->user();
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => "Vous devez vous connecter à un centre !"
            ], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validate([
            'campagne_id'   => 'required|exists:campagnes,id',
            'patient_id'    => 'required|exists:clients,id',
            'consultant_id' => 'nullable|exists:consultants,id',
            'status'        => 'nullable|in:pending,paid,cancelled',
            'billing_date'  => 'nullable|date',
        ]);

        $centre   = Centre::findOrFail($centreId);
        $campagne = Campagne::with('elements')->findOrFail($validated['campagne_id']);

        // Vérification période
        if (now()->endOfDay()->greaterThan(Carbon::parse($campagne->end_date)->endOfDay())) {
            return response()->json([
                'message' => 'La campagne est expirée ou inactive. Facturation bloquée.'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Création de la prestation
            $prestation = Prestation::create([
                'created_by'         => $auth->id,
                'updated_by'         => $auth->id,
                'client_id'          => $validated['patient_id'],
                'consultant_id'      => $validated['consultant_id'],
                'centre_id'          => $centreId,
                'type'               => TypePrestation::CAMPAGNE->value, // ou 7 si tu préfères
                'programmation_date' => now(),
                'campagne_id'        => $validated['campagne_id'],
                'is_campagne'        => true,
            ]);

            // Création de la facture
            $facture = CampagneFacture::create([
                'code'          => $centre->reference . now()->format('Ymd') . Str::upper(Str::random(7)),
                'prestation_id' => $prestation->id,
                'campagne_id'   => $campagne->id,
                'patient_id'    => $validated['patient_id'],
                'consultant_id' => $validated['consultant_id'],
                'centre_id'     => $centreId,
                'amount'        => $campagne->price,
                'type'          => 2,
                'date_fact'     => now(),
                'created_by'    => $auth->id,
                'updated_by'    => $auth->id,
                'status'        => $validated['status'] ?? 'pending',
            ]);

            // Attachement des éléments de la campagne
            foreach ($campagne->elements as $element) {
                if ($element->type === 'examens') {
                    // Vérifier si l'examen est déjà attaché
                    $exists = $prestation->examens()
                        ->where('examens.id', $element->element_id)
                        ->exists();

                    $pivotData = [
                        'quantity'        => 1,
                        'pu'              => $element->price ?? $campagne->price,
                        'b'               => $element->b ?? null,
                        'remise'          => 0,
                        'amount_regulate' => 0,
                        'prelevements'    => [], // Toujours initialisé pour permettre le prélèvement
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];

                    if ($exists) {
                        $prestation->examens()->updateExistingPivot($element->element_id, $pivotData);
                    } else {
                        $prestation->examens()->attach($element->element_id, $pivotData);
                    }

                } elseif ($element->type === 'consultations') {
                    $prestation->consultations()->syncWithoutDetaching([
                        $element->element_id => [
                            'quantity'   => 1,
                            'pu'         => $element->price ?? $campagne->price,
                            'date_rdv'   => now(),
                            'remise'     => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message'    => "Facture et prestation de campagne créées avec succès",
                'facture'    => $facture->load(['campagne', 'patient', 'consultant']),
                'prestation' => $prestation->load(['examens', 'consultations']),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => "Erreur lors de la création de la campagne.",
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission CampagneFactureController::update
     * @permission_desc Modifier une campagne facturée
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => "Vous devez vous connecter à un centre !"
            ], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validate([
            'campagne_id'   => 'required|exists:campagnes,id',
            'patient_id'    => 'required|exists:clients,id',
            'consultant_id' => 'nullable|exists:consultants,id',
            'status'        => 'nullable|in:pending,paid,cancelled',
        ]);

        $facture = CampagneFacture::findOrFail($id);

        // Optionnel : vérifier que la facture appartient bien à ce centre
        if ($facture->centre_id !== (int)$centreId) {
            return response()->json([
                'message' => "Accès refusé à cette facture."
            ], Response::HTTP_FORBIDDEN);
        }
        $prestations = Prestation::where('campagne_id', $facture->campagne_id)
            ->where('client_id', $facture->patient_id)
            ->where('centre_id', $centreId)
            ->get();



        DB::beginTransaction();

        try {
            // Mise à jour de la facture uniquement
            $facture->update([
                'updated_by'    => $auth->id,
                'patient_id'    => $validated['patient_id'],
                'consultant_id' => $validated['consultant_id'],
                'campagne_id'   => $validated['campagne_id'],
            ]);

            foreach ($prestations as $prestation) {
                $prestation->update([
                    'updated_by'    => $auth->id,
                    'client_id'     => $validated['patient_id'],
                    'consultant_id' => $validated['consultant_id'],
                    'campagne_id'   => $validated['campagne_id'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => "Facture mise à jour avec succès",
                'facture' => $facture->load(['campagne', 'patient', 'consultant']),
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => "Erreur lors de la mise à jour de la facture.",
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $facture_campagne = CampagneFacture::with(
            'campagne',
            'patient',
            'consultant',
            'centre'
        )->findOrFail($id);

        return response()->json([
            'facture_campagne' => $facture_campagne
        ]);
    }






    //
}
