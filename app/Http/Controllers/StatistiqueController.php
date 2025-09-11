<?php

namespace App\Http\Controllers;

use App\Enums\StateFacture;
use App\Enums\TypeClient;
use App\Enums\TypePrestation;
use App\Models\Client;
use App\Models\Facture;
use App\Models\RendezVous;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @permission_category Gestion des statistiques
 */

class StatistiqueController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission StatistiqueController::clientsJourParType
     * @permission_desc Statistiques des types de clients par jour
     */
    public function clientsJourParType()
    {
        $today = Carbon::today();

        $response = [];

        foreach (TypeClient::cases() as $type) {
            $typeValue = $type->value;

            // Nouveaux clients aujourd'hui par type
            $nouveaux = Client::whereDate('created_at', $today)
                ->where('type_cli', $typeValue)
                ->count();

            // Anciens clients ayant eu une activité aujourd'hui par type
            $anciens = Client::whereDate('created_at', '<', $today)
                ->where('type_cli', $typeValue)
                ->count();

            $response[$typeValue] = [
                'nouveaux' => $nouveaux,
                'anciens' => $anciens,
            ];
        }

        return response()->json($response);
    }


    /**
     * Display a listing of the resource.
     * @permission StatistiqueController::statistiquesAujourdHui
     * @permission_desc Statistiques de Rendez-vous par état
     */
    public function statistiquesAujourdHui()
    {
        try {
            $aujourdhui = now()->startOfDay();  // date à 00h00 aujourd'hui
            $finJournee = now()->endOfDay();    // date à 23h59:59 aujourd'hui

            // Total des rendez-vous aujourd'hui
            $totalParJour = RendezVous::where('is_deleted', false)
                ->whereBetween('created_at', [$aujourdhui, $finJournee])
                ->count();

            // Rendez-vous par état aujourd'hui
            $parEtat = RendezVous::select('etat', DB::raw('count(*) as total'))
                ->where('is_deleted', false)
                ->whereBetween('created_at', [$aujourdhui, $finJournee])
                ->groupBy('etat')
                ->get();

            // Rendez-vous par type aujourd'hui
            $parType = RendezVous::select('type', DB::raw('count(*) as total'))
                ->where('is_deleted', false)
                ->whereBetween('created_at', [$aujourdhui, $finJournee])
                ->groupBy('type')
                ->get();

            return response()->json([
                'total_rendez_vous_aujourdhui' => $totalParJour,
                'rendez_vous_par_etat_aujourdhui' => $parEtat,
                'rendez_vous_par_type_aujourdhui' => $parType,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors du chargement des statistiques d\'aujourd\'hui.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission StatistiqueController::getAllFacture
     * @permission_desc Statistiques de Factures par type de prestation et état
     */
    public function getAllFacture()
    {
        try {
            $today = Carbon::today();

            $factures = Facture::selectRaw("
                DATE(date_fact) as jour,
                prestation_id,
                CASE
                    WHEN state = ? THEN 'Soldé'
                    ELSE 'Non soldé'
                END as etat,
                COUNT(*) as total
            ", [StateFacture::PAID->value])
                ->whereDate('date_fact', $today)
                ->groupByRaw("DATE(date_fact), prestation_id, state")
                ->with('prestation:id,type')
                ->orderByRaw("DATE(date_fact)")
                ->get();

            $data = [];

            foreach ($factures as $facture) {
                $date = $facture->jour;

                $prestationType = $facture->prestation?->type
                    ? TypePrestation::label($facture->prestation->type)
                    : 'Inconnu';

                $etat = $facture->etat;

                if (!isset($data[$date])) {
                    $data[$date] = [];
                }

                if (!isset($data[$date][$prestationType])) {
                    $data[$date][$prestationType] = [
                        'Soldé' => 0,
                        'Non soldé' => 0,
                    ];
                }

                $data[$date][$prestationType][$etat] = $facture->total;
            }

            return response()->json($data);

        } catch (\Throwable $e) {
            Log::error('Erreur dans getAllFacture: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Erreur interne'], 500);
        }
    }




    //
}
