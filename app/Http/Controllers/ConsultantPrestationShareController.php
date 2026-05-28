<?php

namespace App\Http\Controllers;

use App\Enums\TypePrestation;
use App\Models\Caisse;
use App\Models\Centre;
use App\Models\Consultant;
use App\Models\ConsultantPaymentPrestation;
use App\Models\ConsultantPrestationShare;
use App\Models\Prestation;
use App\Models\PrestationCategory;
use App\Models\SessionCaisse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
/**
 * @permission_category Gestion des prestations
 * @permission_module Gestion des prestations
 */

class ConsultantPrestationShareController extends Controller
{
    /**
     * @permission ConsultantPrestationShareController::get_all_prestations_type
     * @permission_desc Afficher la liste des prestations d'un consultant
     */
    public function get_all_prestations_type(Request $request)
    {
        $perPage = $request->input('limit', 5);
        $page = $request->input('page', 1);

        $query = PrestationCategory::with(['consultantShares','createdBy','updatedBy'])
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            });
        if($search = trim($request->input('search'))){
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('is_active', 'like', "%{$search}%");
            });
        }
        $nature = $query->latest()->paginate($perPage, ['*'], 'page', $page);
        return response()->json([
            'data'         => $nature->items(),
            'current_page' => $nature->currentPage(),
            'last_page'    => $nature->lastPage(),
            'total'        => $nature->total(),
        ]);
    }



    /**
     * @permission ConsultantPrestationShareController::save_commisions_for_consultants
     * @permission_desc Enregistrer les commissions des prestations d'un consultant
     */
    public function save_commisions_for_consultants(Request $request)
    {
        $auth = auth()->user();

        $validated = $request->validate([
            'consultant_id' => 'required|exists:consultants,id',

            'account_id' => 'nullable|exists:payment_accounts,id',
            'apply_on_care' => 'nullable|boolean',
            'apply_on_clients' => 'nullable|boolean',

            'prestations' => 'required|array',
            'prestations.*.prestation_type_id' => 'required|exists:type_prestations,id',
            'prestations.*.calculation_type' => 'required|in:fixed,percentage',
            'prestations.*.price' => 'nullable|numeric|min:0',
            'prestations.*.share_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $consultantId = $validated['consultant_id'];

        $incomingIds = collect($validated['prestations'])
            ->pluck('prestation_type_id')
            ->toArray();

        // ❌ delete removed
        ConsultantPrestationShare::where('consultant_id', $consultantId)
            ->whereNotIn('prestation_type_id', $incomingIds)
            ->delete();

        foreach ($validated['prestations'] as $item) {

            $type = $item['calculation_type'];

            if (!in_array($type, ['fixed', 'percentage'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type invalide'
                ], 422);
            }

            $data = [
                'consultant_id' => $consultantId,
                'prestation_type_id' => $item['prestation_type_id'],
                'calculation_type' => $type,
                'price' => null,
                'share_rate' => null,
                'account_id' => $validated['account_id'] ?? null,
                'apply_on_care' => $validated['apply_on_care'] ?? false,
                'apply_on_clients' => $validated['apply_on_clients'] ?? false,

                'updated_by' => $auth->id,
            ];

            // FIXED
            if ($type === 'fixed') {

                if (empty($item['price'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Prix obligatoire pour fixed'
                    ], 422);
                }

                $data['price'] = (float) $item['price'];
            }

            // PERCENTAGE
            if ($type === 'percentage') {

                if (empty($item['share_rate'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Taux obligatoire pour percentage'
                    ], 422);
                }

                $data['share_rate'] = (float) $item['share_rate'];
            }

            ConsultantPrestationShare::updateOrCreate(
                [
                    'consultant_id' => $consultantId,
                    'prestation_type_id' => $item['prestation_type_id'],
                ],
                $data
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Commissions synchronisées avec succès.',
        ], 200);
    }

    public function show($id)
    {
        return ConsultantPrestationShare::with(['consultant', 'prestationType','createdBy','updatedBy','account'])->findOrFail($id);
    }


    /**
     * @permission ConsultantPrestationShareController::get_all_paiement_for_consultants
     * @permission_desc Afficher la liste des prestations d'un consultant sur une période
     */
    public function get_all_paiement_for_consultants(Request $request, $consultant_id)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => 'Centre non fourni'
            ], 400);
        }

        // ✅ sécurisation des dates
        $start_date = $request->input('start_date')
            ? \Carbon\Carbon::parse($request->input('start_date'))->startOfDay()
            : null;

        $end_date = $request->input('end_date')
            ? \Carbon\Carbon::parse($request->input('end_date'))->endOfDay()
            : null;

        $query = Prestation::with([
            'centre',
            'factures',
            'client',
            'consultant',
            'payableBy',
            'actes',
            'soins',
            'consultations',
            'hospitalisations',
            'products',
            'examens',
            'prestationables'
        ])
            ->where('centre_id', $centreId)
            ->where('consultant_id', $consultant_id)
            ->where('consultant_amount_status', 'available')
            ->where('consultant_amount', '>', 0);

        // ✅ filtre date seulement si fourni
        if ($start_date && $end_date) {
            $query->whereBetween('created_at', [$start_date, $end_date]);
        }
        $totalConsultantAmount = (clone $query)->sum('consultant_amount');

        $results = $query->latest()->paginate(
            $perPage,
            ['*'],
            'page',
            $page
        );

        return response()->json([
            'data' => $results->items(),
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
            'total' => $results->total(),
            'total_consultant_amount' => $totalConsultantAmount,
        ]);
    }


    /**
     * @permission ConsultantPrestationShareController::store_paiement_consultant
     * @permission_desc Enregistrer le paiement des prestations d'un consultant par une secrétaire
     */
    public function store_paiement_consultant(Request $request)
    {
        $auth = auth()->user();

        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => 'Centre non fourni'
            ], 400);
        }

        $request->validate([
            'consultant_id' => 'required|integer|exists:consultants,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'description' => 'required',
            'amount' => 'required|numeric|min:1',
            'prestation_ids' => 'required|array',
            'prestation_ids.*' => 'integer|exists:prestations,id',
        ]);

        DB::beginTransaction();

        try {

            $consultantShare = ConsultantPrestationShare::where('consultant_id', $request->consultant_id)->first();
            $accountId = $consultantShare?->account_id;

            if (!$accountId) {
                return response()->json([
                    'message' => 'Aucun compte de paiement configuré pour ce consultant.'
                ], 422);
            }

            Log::info($accountId);
            Log::info($consultantShare);
            Log::info($centreId);
            Log::info($auth->id);

            $sessionCaisse = SessionCaisse::where('user_id', $auth->id)->where('centre_id', $centreId)->whereNull('fermeture_ts')->where('etat', 'OUVERTE')
                ->first();

            Log::info($sessionCaisse);

            if (!$sessionCaisse) {
                return response()->json([
                    'message' => 'Aucune session de caisse active dans ce centre.'
                ], 403);
            }

            $caisse = Caisse::where('user_id', $auth->id)
                ->where('centre_id', $centreId)
                ->where('type_caisse', 'small_caisse')
                ->first();

            if (!$caisse) {
                return response()->json([
                    'message' => 'Caisse introuvable pour cet utilisateur.'
                ], 404);
            }

            if ((float) $sessionCaisse->solde < (float) $request->amount) {
                return response()->json([
                    'message' => 'Solde insuffisant dans la caisse.'
                ], 422);
            }

            $payment = ConsultantPaymentPrestation::create([
                'consultant_id' => $request->consultant_id,
                'account_id' => $accountId,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'description' => $request->description,
                'amount' => $request->amount,
                'caisse_id' => $caisse->id,
                'centre_id' => $centreId,
                'created_by' => $auth->id,
            ]);

            $sessionCaisse->update([
                'solde' => (float) $sessionCaisse->solde - (float) $request->amount,
                'current_sold' => (float) $sessionCaisse->current_sold - (float) $request->amount,
                'sold_without_small_change' => (float) $sessionCaisse->sold_without_small_change - (float) $request->amount
            ]);

            $updated = Prestation::whereIn('id', $request->prestation_ids)
                ->where('consultant_id', $request->consultant_id)
                ->where('consultant_amount_status', 'available')
                ->update([
                    'consultant_amount_status' => 'paid'
                ]);

            DB::commit();

            return response()->json([
                'message' => 'Paiement consultant effectué avec succès.',
                'updated_prestations' => $updated,
                'data' => $payment
            ], 201);

        } catch (\Throwable $th) {

            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors du paiement consultant.',
                'error' => $th->getMessage()
            ], 500);
        }
    }


    /**
     * @permission ConsultantPrestationShareController::store_paiement_consultant_before_center
     * @permission_desc Enregistrer le paiement des prestations par un décisionnel
     */
    public function store_paiement_consultant_before_center(Request $request)
    {
        $auth = auth()->user();

        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => 'Centre non fourni'
            ], 400);
        }

        $request->validate([
            'consultant_id' => 'required|integer|exists:consultants,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'description' => 'required',
            'amount' => 'required|numeric|min:1',
            'prestation_ids' => 'required|array',
            'prestation_ids.*' => 'integer|exists:prestations,id',
        ]);

        DB::beginTransaction();

        try {

            $consultantShare = ConsultantPrestationShare::where('consultant_id', $request->consultant_id)->first();
            $accountId = $consultantShare?->account_id;

            if (!$accountId) {
                return response()->json([
                    'message' => 'Aucun compte de paiement configuré pour ce consultant.'
                ], 422);
            }

            Log::info($accountId);
            Log::info($consultantShare);
            Log::info($centreId);
            Log::info($auth->id);


            $caisse = Caisse::where('centre_id', $centreId)
                ->where('type_caisse', 'consolidation_caisse')
                ->first();

            if (!$caisse) {
                return response()->json([
                    'message' => 'Caisse introuvable dans ce centre.'
                ], 404);
            }

            if ((float) $caisse->solde_caisse < (float) $request->amount) {
                return response()->json([
                    'message' => 'Solde insuffisant dans la caisse de ce centre.'
                ], 422);
            }

            $payment = ConsultantPaymentPrestation::create([
                'consultant_id' => $request->consultant_id,
                'account_id' => $accountId,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'description' => $request->description,
                'amount' => $request->amount,
                'caisse_id' => $caisse->id,
                'centre_id' => $centreId,
                'created_by' => $auth->id,
            ]);

            $caisse->update([
                'solde_caisse' => max(0, (float) $caisse->solde_caisse - (float) $request->amount)
            ]);

            $updated = Prestation::whereIn('id', $request->prestation_ids)
                ->where('consultant_id', $request->consultant_id)
                ->where('consultant_amount_status', 'available')
                ->update([
                    'consultant_amount_status' => 'paid'
                ]);

            DB::commit();

            return response()->json([
                'message' => 'Paiement consultant effectué avec succès.',
                'updated_prestations' => $updated,
                'data' => $payment
            ], 201);

        } catch (\Throwable $th) {

            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors du paiement consultant.',
                'error' => $th->getMessage()
            ], 500);
        }
    }


    /**
     * @permission ConsultantPrestationShareController::get_consultant_paid
     * @permission_desc Imprimer l'état des prestations d'un consultant déjà reglées
     */
    public function get_consultant_paid(Request $request, $consultant_id)
    {
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => 'Centre non fourni'
            ], 400);
        }

        $start_date = \Illuminate\Support\Carbon::parse($request->input('start_date'))->startOfDay();
        $end_date = \Illuminate\Support\Carbon::parse($request->input('end_date'))->endOfDay();

        // ✅ Query optimisée
        $query = Prestation::with([
            'centre',
            'factures',
            'client',
            'consultant',
            'payableBy',
            'actes',
            'soins',
            'consultations',
            'hospitalisations',
            'products',
            'examens',
            'prestationables'
        ])
            ->where('centre_id', $centreId)
            ->where('consultant_id', $consultant_id)
            ->where('consultant_amount_status', 'paid')
            ->where('consultant_amount', '>', 0)
            ->whereBetween('created_at', [$start_date, $end_date]);

        $result = $query->orderBy('created_at', 'ASC')->get();

        logger()->info('📊 CONSULTANT PAID RESULT DEBUG', [
            'count' => $result->count(),
            'ids' => $result->pluck('id'),
            'first_item' => $result->first(),
        ]);

        if ($result->isEmpty()) {
            return response()->json([
                'message' => 'Aucune donnée trouvée.'
            ], 404);
        }

        $centre = Centre::find($centreId);
        $media = $centre?->medias()->where('name', 'logo')->first();
        $consultant = Consultant::find($consultant_id);

        $data = [
            'result' => $result,
            'logo' => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
            'centre' => $centre,
            'start' => $start_date,
            'end' => $end_date,
            'consultant' => $consultant,
        ];

        $fileName = 'ETATS-DU-CONSULTANTS-' . now()->format('YmdHis') . '.pdf';

        $folderPath = 'storage/prestations-consultants-paids';
        $filePath = $folderPath . '/' . $fileName;

        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0755, true);
        }
        $footer = 'pdfs.reports.factures.footer';

        if (!file_exists($filePath)) {
            save_browser_shot_pdf(
                view: 'pdfs.prestations-consultants-paids.prestations-consultants-paids',
                data: $data,
                folderPath: $folderPath,
                path: $filePath,
                margins: [5, 5, 5, 5],
                footer: $footer,
                format: 'A5',
                direction: 'landscape'
            );
        }

        if (!file_exists($filePath)) {
            return response()->json([
                'message' => 'Le fichier PDF n\'a pas été généré.'
            ], 500);
        }

        // 🔹 Base64
        $pdfContent = file_get_contents($filePath);
        $base64 = base64_encode($pdfContent);

        return response()->json([
            'result' => $result,
            'base64' => $base64,
            'url' => $filePath,
            'filename' => $fileName,
        ], 200);
    }


    /**
     * @permission ConsultantPrestationShareController::get_consultant_not_paid
     * @permission_desc Imprimer l'état des prestations d'un consultant non reglées
     */
    public function get_consultant_not_paid(Request $request, $consultant_id)
    {
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => 'Centre non fourni'
            ], 400);
        }

        $start_date = \Illuminate\Support\Carbon::parse($request->input('start_date'))->startOfDay();
        $end_date = \Illuminate\Support\Carbon::parse($request->input('end_date'))->endOfDay();

        // ✅ Query optimisée
        $query = Prestation::with([
            'centre',
            'factures',
            'client',
            'consultant',
            'payableBy',
            'actes',
            'soins',
            'consultations',
            'hospitalisations',
            'products',
            'examens',
            'prestationables'
        ])
            ->where('centre_id', $centreId)
            ->where('consultant_id', $consultant_id)
            ->whereIn('consultant_amount_status', ['available', 'pending'])
            ->where('consultant_amount', '>', 0)
            ->whereBetween('created_at', [$start_date, $end_date]);

        $result = $query->orderBy('created_at', 'ASC')->get();

        logger()->info('📊 CONSULTANT PAID RESULT DEBUG', [
            'count' => $result->count(),
            'ids' => $result->pluck('id'),
            'first_item' => $result->first(),
        ]);

        if ($result->isEmpty()) {
            return response()->json([
                'message' => 'Aucune donnée trouvée.'
            ], 404);
        }

        $centre = Centre::find($centreId);
        $media = $centre?->medias()->where('name', 'logo')->first();
        $consultant = Consultant::find($consultant_id);

        $data = [
            'result' => $result,
            'logo' => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
            'centre' => $centre,
            'start' => $start_date,
            'end' => $end_date,
            'consultant' => $consultant,
        ];

        $fileName = 'ETATS-DU-CONSULTANTS-' . now()->format('YmdHis') . '.pdf';

        $folderPath = 'storage/prestations-consultants-not-paids';
        $filePath = $folderPath . '/' . $fileName;

        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0755, true);
        }
        $footer = 'pdfs.reports.factures.footer';

        if (!file_exists($filePath)) {
            save_browser_shot_pdf(
                view: 'pdfs.prestations-consultants-not-paids.prestations-consultants-not-paids',
                data: $data,
                folderPath: $folderPath,
                path: $filePath,
                margins: [5, 5, 5, 5],
                footer: $footer,
                format: 'A5',
                direction: 'landscape'
            );
        }

        if (!file_exists($filePath)) {
            return response()->json([
                'message' => 'Le fichier PDF n\'a pas été généré.'
            ], 500);
        }

        // 🔹 Base64
        $pdfContent = file_get_contents($filePath);
        $base64 = base64_encode($pdfContent);

        return response()->json([
            'result' => $result,
            'base64' => $base64,
            'url' => $filePath,
            'filename' => $fileName,
        ], 200);
    }


}
