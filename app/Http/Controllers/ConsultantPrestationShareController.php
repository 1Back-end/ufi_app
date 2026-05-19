<?php

namespace App\Http\Controllers;

use App\Enums\TypePrestation;
use App\Models\ConsultantPrestationShare;
use App\Models\Prestation;
use App\Models\PrestationCategory;
use Illuminate\Http\Request;

class ConsultantPrestationShareController extends Controller
{
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



    public function save_commisions_for_consultants(Request $request)
    {
        $auth = auth()->user();

        $validated = $request->validate([
            'consultant_id' => 'required|exists:consultants,id',
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
        \App\Models\ConsultantPrestationShare::where('consultant_id', $consultantId)
            ->whereNotIn('prestation_type_id', $incomingIds)
            ->delete();

        foreach ($validated['prestations'] as $item) {

            // 🔧 sécurité nettoyage
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

            // 🔥 FIX IMPORTANT (anti duplicate error)
            \App\Models\ConsultantPrestationShare::updateOrCreate(
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
        return ConsultantPrestationShare::with(['consultant', 'prestationType','createdBy','updatedBy'])->findOrFail($id);
    }


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



}
