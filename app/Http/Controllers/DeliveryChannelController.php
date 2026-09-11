<?php

namespace App\Http\Controllers;

use App\Models\DeliveryChannel;
use App\Models\Prestation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @permission_category Gestion du canal de remise des résultats du labo
 * @permission_module Gestion des prestations
 * @permission_module Gestion du laboratoire
 */
class DeliveryChannelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @permission DeliveryChannelController::index
     * @permission_desc Afficher la liste des canaux de remise des résultats du labo
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 5);
        $page = $request->input('page', 1);

        $query = DeliveryChannel::with(['creator:id,nom_utilisateur', 'updater:id,nom_utilisateur'])
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
     * Store a newly created resource in storage.
     *
     * @permission DeliveryChannelController::store
     * @permission_desc Créer un canal de remise des résultats du labo
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:delivery_channels,name',
            'code' => 'nullable|string|max:50|unique:delivery_channels,code',
            'slug' => 'nullable|string|max:255|unique:delivery_channels,slug',
            'description' => 'nullable|string',
            'is_patient_info' => 'boolean',
        ]);

        $validated['name'] = mb_strtoupper($validated['name']);
        $validated['slug'] = $request->filled('slug') ? mb_strtolower($request->input('slug')) : mb_strtolower($validated['name']);
        $validated['is_patient_info'] = $request->boolean('is_patient_info');
        $validated['created_by'] = $auth->id;

        $channel = DeliveryChannel::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Canal de remise créé avec succès.',
            'data' => $channel
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @permission DeliveryChannelController::show
     * @permission_desc Afficher les détails d'un canal de remise des résultats du labo
     *
     * @param \App\Models\DeliveryChannel $deliveryChannel
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(DeliveryChannel $deliveryChannel)
    {
        return response()->json([
            'success' => true,
            'data' => $deliveryChannel->load(['creator', 'updater'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @permission DeliveryChannelController::update
     * @permission_desc Modifier un canal de remise des résultats du labo
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\DeliveryChannel $deliveryChannel
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, DeliveryChannel $deliveryChannel)
    {
        $auth = auth()->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:delivery_channels,name,' . $deliveryChannel->id,
            'code' => 'nullable|string|max:50|unique:delivery_channels,code,' . $deliveryChannel->id,
            'slug' => 'nullable|string|max:255|unique:delivery_channels,slug,' . $deliveryChannel->id,
            'description' => 'nullable|string',
            'is_patient_info' => 'boolean',
        ]);

        $validated['name'] = mb_strtoupper($validated['name']);
        $validated['slug'] = $request->filled('slug') ? mb_strtolower($request->input('slug')) : mb_strtolower($validated['name']);
        $validated['is_patient_info'] = $request->boolean('is_patient_info');

        $deliveryChannel->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Canal de remise mis à jour avec succès.',
            'data' => $deliveryChannel
        ], 200);
    }


    /**
     * Update the status (active/inactive) of the specified resource.
     *
     * @permission DeliveryChannelController::updateStatus
     * @permission_desc Activer ou désactiver un canal de remise des résultats du labo
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\DeliveryChannel $deliveryChannel
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, DeliveryChannel $deliveryChannel)
    {
        $auth = auth()->user();

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $deliveryChannel->update([
            'is_active' => $validated['is_active'],
            'updated_by' => $auth->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès.',
            'data' => $deliveryChannel
        ]);
    }



    /**
     * Remove the specified resource from storage.
     *
     * @permission DeliveryChannelController::destroy
     * @permission_desc Supprimer un canal de remise des résultats du labo
     *
     * @param \App\Models\DeliveryChannel $deliveryChannel
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(DeliveryChannel $deliveryChannel)
    {
        $deliveryChannel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Canal de remise supprimé avec succès.'
        ]);
    }

    public function get_resultats_by_chanal_delivey(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDay()->startOfDay()->toDateTimeString());
        $endDate = $request->input('end_date', now()->endOfDay()->toDateTimeString());

        $query = Prestation::query()
            ->select(
                'delivery_channels_id',
                DB::raw('DATE(result_delivered_at) as delivery_date'),
                DB::raw('count(*) as total')
            )
            ->with('delivery_chanel')
            ->whereNotNull('delivery_channels_id')
            ->whereNotNull('result_delivered_at')
            ->whereBetween('result_delivered_at', [$startDate, $endDate]);

        $stats = $query->groupBy('delivery_channels_id', DB::raw('DATE(result_delivered_at)'))
            ->orderBy('delivery_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
